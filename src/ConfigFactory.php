<?php

/*
 * LICENSE: This file is subject to the terms and conditions defined in
 * file 'LICENSE', which is part of this source code package.
 *
 * @copyright 2016 Copyright(c) - All rights reserved.
 *
 * @author Rafael SR <https://github.com/rafrsr>
 * @package Licenser
 * @version 1.0.4
 */

namespace Rafrsr\Licenser;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Yaml\Yaml;

/**
 * ConfigFactory.
 */
class ConfigFactory
{
    /**
     * Array containing configuration.
     *
     * e.g.
     * [
     *  'finder' => ['in'=>'absolute/path/to/src']
     *  'license' => 'mit',
     *  'parameters => ['author'=>'AuthorName']
     * ]
     * All paths given in the array should be absolute paths.
     * At this point licenser can`t resolve relative paths
     *
     * @param array $configArray
     *
     * @throws \InvalidArgumentException
     *
     * @return Config
     */
    public static function createFromArray($configArray)
    {
        if (isset($configArray['license_content'])) {
            $license = $configArray['license_content'];
        } else {
            $license = isset($configArray['license']) ? $configArray['license'] : 'default';
            if (file_exists($license)) {
                $license = file_get_contents($license);
            } elseif (file_exists(self::resolveBuildInLicense($license))) {
                $license = file_get_contents(self::resolveBuildInLicense($license));
            } else {
                throw new \InvalidArgumentException(sprintf('Invalid license file "%s"', $license));
            }
        }

        $parameters = [];
        if (isset($configArray['parameters'])) {
            foreach ($configArray['parameters'] as $name => $value) {
                //try to resolve constants
                if (strpos($value, '@') === 0 && defined(substr($value, 1))) {
                    $value = constant(substr($value, 1));
                }

                $parameters[$name] = $value;
            }
        }

        if (!isset($configArray['finder']['in'])) {
            throw new \LogicException('Invalid configuration, at least one source is required to locate files.');
        }

        if (!isset($configArray['finder']['name'])) {
            $configArray['finder']['name'] = '*.php'; //default file types
        }

        $config = Config::create()
            ->setLicense($license)
            ->setParameters($parameters)
            ->setFinderBuilder(FinderBuilder::create($configArray['finder']));

        return $config;
    }

    /**
     * Create a config using command line arguments and options.
     *
     * @param InputInterface $input
     *
     * @return Config
     */
    public static function createFromCommandLine(InputInterface $input)
    {
        $source = $input->getArgument('source');
        $license = $input->getArgument('license');
        $configArray = [];

        $finder = self::extractFinderSettingsFromCommandLine($input);
        if (file_exists($source)) {
            if (is_dir($source)) {
                $finder['name'] = '*.php';
                $finder['in'] = realpath($source);
            } else {
                $file = new \SplFileInfo($source);
                $finder['name'] = $file->getFilename();
                $finder['in'] = realpath($file->getPath());
                $finder['depth'] = 0;
            }
        } else {
            throw new FileNotFoundException(null, 0, null, $source);
        }
        $configArray['finder'] = $finder;
        $configArray['parameters'] = self::extractParametersFromCommandLine($input);
        $configArray['license'] = $license;

        return self::createFromArray($configArray);
    }

    /**
     * Return array of all possible configurations contained in the config file,
     * one simple file can be converted to multiple configurations
     * when `finders` is used.
     *
     * @param \SplFileInfo   $configFile file containing the configuration, *.yml
     * @param InputInterface $input      command line input, used to override some configuration in the file using command line
     *
     * @return array|Config[]
     */
    public static function createFromConfigFile(\SplFileInfo $configFile, InputInterface $input = null)
    {
        $configArray = Yaml::parse(file_get_contents($configFile->getRealPath()));
        $workingDir = $configFile->getPath();
        if (isset($configArray['finder'])) {
            $configArray['finders'][] = $configArray['finder'];
            unset($configArray['finder']);
        }

        if (!isset($configArray['finders'])) {
            throw new \LogicException('Invalid configuration, configure at least one `finder` node.');
        }

        $configs = [];
        foreach ($configArray['finders'] as $finder) {
            $config = array_merge($configArray, ['finder' => $finder]);
            unset($config['finders']);
            $configs[] = self::parseConfigArray($config, $workingDir);
        }

        if ($input) {
            foreach ($configs as $config) {
                if ($source = self::getSource($input)) {
                    self::overrideConfigSource($config, $source);
                }
            }
        }

        return $configs;
    }

    /**
     * parseConfigArray.
     *
     * @param array  $configArray array of config in the config file
     * @param string $workingDir  current working dir, required to resolve relative paths
     *
     * @throws \LogicException
     *
     * @return Config
     */
    private static function parseConfigArray($configArray, $workingDir)
    {
        if (!isset($configArray['finder']['in'])) {
            throw new \LogicException('Invalid configuration, at least one source is required to locate files.');
        }

        //resolve absolute dirs
        $ins = [];
        foreach ((array) $configArray['finder']['in'] as $in) {
            $ins[] = realpath($workingDir.DIRECTORY_SEPARATOR.$in);
        }
        $configArray['finder']['in'] = $ins;

        if (!array_key_exists('license_content', $configArray)) {
            $configArray['license'] = array_key_exists('license', $configArray) ? $configArray['license'] : 'default';
            //resolve absolute file
            if (!file_exists($configArray['license']) && file_exists($workingDir.DIRECTORY_SEPARATOR.$configArray['license'])) {
                $configArray['license'] = realpath($workingDir.DIRECTORY_SEPARATOR.$configArray['license']);
            }
        }

        return self::createFromArray($configArray);
    }

    /**
     * Extract source from input.
     *
     * @param InputInterface $input
     *
     * @return bool
     */
    private static function getSource(InputInterface $input)
    {
        $validSource = ($source = $input->getArgument('source')) && file_exists($source);

        if ($validSource && $input->getOption('config')) {
            //if the source file is the config file, is not a source
            if (realpath($source) === realpath($input->getOption('config'))) {
                $source = null;
            }
        } elseif (!$validSource) {
            $source = null;
        }

        return $source;
    }

    /**
     * allow source override by command
     * https://github.com/rafrsr/licenser/issues/1
     * Add current path and change the name from *.php to given filename in case of file.
     *
     * @param Config $config
     * @param        $source
     */
    private static function overrideConfigSource(Config $config, $source)
    {
        $source = realpath($source);
        $ins = $config->getFinderBuilder()->getIn();
        //obtaining related path based on "in" directories
        //allowing filter results using the finder config
        foreach ($ins as $in) {
            if (strpos($source, $in) !== false) {
                $path = str_replace($in, null, $source);
                //remove any \ or / in the path start
                $path = preg_replace('/^[\/\\\]/', null, $path);
                $path = str_replace('\\', '/', $path); //convert \ -> /
                if (is_file($source)) {
                    $path = pathinfo($path, PATHINFO_DIRNAME);
                }
                $config->getFinderBuilder()->clearPath()->path($path);
            }
        }

        if (is_file($source)) {
            $config->getFinderBuilder()->clearName()->name(pathinfo($source, PATHINFO_BASENAME));
        }
    }

    /**
     * Extract array of parameters from command line input.
     *
     * @param InputInterface $input
     *
     * @return array
     */
    private static function extractParametersFromCommandLine(InputInterface $input)
    {
        $params = [];
        if ($input->getOption('param')) {
            foreach ($input->getOption('param') as $param) {
                if (strpos($param, ':') !== false) {
                    list($name, $value) = explode(':', $param, 2);
                    $params[$name] = $value;
                } else {
                    $msg = sprintf('Invalid parameter "%s", should have the format "name:value", e.g. -p year:%s -p owner:"My Name <email@example.com>"', $param, date('Y'));
                    throw new \InvalidArgumentException($msg);
                }
            }
        }

        return $params;
    }

    /**
     * Extract settings finder from command line.
     *
     * @param InputInterface $input
     *
     * @return array
     */
    private static function extractFinderSettingsFromCommandLine(InputInterface $input)
    {
        $finder = [];
        if ($input->getOption('finder')) {
            foreach ($input->getOption('finder') as $finderOption) {
                if (strpos($finderOption, ':') !== false) {
                    list($option, $value) = explode(':', $finderOption, 2);
                    $finder[$option][] = $value;
                } else {
                    $msg = sprintf('Invalid finder option "%s", should have the format "option:value", e.g. -f notName:*Test.php', $finderOption);
                    throw new \InvalidArgumentException($msg);
                }
            }
        }

        return $finder;
    }

    /**
     * Resolve local license full path for passed build in license name.
     *
     * @param string $license
     *
     * @return null|string string with full path or null if the license cant be resolved
     */
    private static function resolveBuildInLicense($license)
    {
        $license = implode(DIRECTORY_SEPARATOR, [__DIR__, 'licenses', $license]);
        if (file_exists($license)) {
            return $license;
        }

        return;
    }
}