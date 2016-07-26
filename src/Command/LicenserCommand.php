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

namespace Rafrsr\Licenser\Command;

use Rafrsr\Licenser\Config;
use Rafrsr\Licenser\Licenser;
use Rafrsr\Licenser\ProcessLogger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Yaml\Yaml;

/**
 * LicenserCommand.
 *
 * @author RafaelSR <https://github.com/rafrsr>
 */
class LicenserCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('licenser')
            ->setDescription('Runs the licenser against the given source path')
            ->addArgument(
                'source',
                InputArgument::OPTIONAL,
                'The path to the source files that the licenser will process'
            )
            ->addArgument(
                'license',
                InputArgument::OPTIONAL,
                'The name of a built in license or a path to the file containing your custom license header doc block as it will appear when prepended to your source files',
                'default'
            )
            ->addOption(
                'config',
                'c',
                InputOption::VALUE_OPTIONAL,
                '.yml file with configuration'
            )
            ->addOption(
                'param',
                'p',
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Parameters to pass to the license file'
            )
            ->addOption(
                'finder',
                'f',
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Configure the finder'
            )
            ->addOption(
                'check-only',
                '',
                InputOption::VALUE_NONE,
                'Return success if all files are ok, otherwise return error'
            )
            ->addOption(
                'dry-run',
                '',
                InputOption::VALUE_NONE,
                'If specified, the command will report a list of affected files but will make no modifications'
            );
    }

    /**
     * Executes the command.
     *
     * @param InputInterface  $input  An input stream
     * @param OutputInterface $output An output stream
     *
     * @return int null or 0 if everything went fine, or an error code
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dryRun = $input->getOption('dry-run');
        $checkOnly = $input->getOption('check-only');

        $mode = Licenser::MODE_NORMAL;
        if ($checkOnly) {
            $mode = Licenser::MODE_CHECK_ONLY;
        } elseif ($dryRun) {
            $mode = Licenser::MODE_DRY_RUN;
        }

        $logger = new ProcessLogger($output);
        $logger->startProcess($mode);

        if ($yml = $input->getOption('config')) {
            $this->processFromYML($yml, $input, $mode, $logger);
        } else {
            $this->processFromInput($input, $mode, $logger);
        }

        $logger->finishProcess();

        if ($mode === Licenser::MODE_CHECK_ONLY && $logger->countAdditions() + $logger->countUpdates() > 0) {
            return 1;
        }

        return 0;
    }

    /**
     * buildLicenser.
     *
     * @param Config        $config
     * @param ProcessLogger $logger
     *
     * @return Licenser
     */
    protected function buildLicenser(Config $config, ProcessLogger $logger)
    {
        return Licenser::create($config, $logger);
    }

    /**
     * processFromInput.
     *
     * @param InputInterface $input
     * @param string         $mode
     * @param ProcessLogger  $logger
     */
    private function processFromInput(InputInterface $input, $mode, ProcessLogger $logger)
    {
        $configArray = $this->parseInput($input);
        $this->buildLicenser(Config::createFromArray($configArray), $logger)->process($mode);
    }

    /**
     * processFromYML.
     *
     * @param string         $yml
     * @param InputInterface $input
     * @param string         $mode
     * @param ProcessLogger  $logger
     */
    private function processFromYML($yml, InputInterface $input, $mode, ProcessLogger $logger)
    {
        $file = new \SplFileInfo(realpath($yml));
        $workingDir = $file->getPath();

        $ymlConfig = file_get_contents($file->getPathname());
        $configsArray = $this->parseConfigArray(Yaml::parse($ymlConfig), $workingDir);
        foreach ($configsArray as $configArray) {
            //allow source override by command
            //https://github.com/rafrsr/licenser/issues/1
            if ($source = $this->getSource($input)) {
                $this->overrideConfigSource($configArray, $source);
            }

            $this->buildLicenser(Config::createFromArray($configArray), $logger)->process($mode);
        }
    }

    /**
     * Extract source from input
     *
     * @param InputInterface $input
     *
     * @return bool
     */
    private function getSource(InputInterface $input)
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
     * Add current path and change the name from *.php to given filename in case of file
     *
     * @param $configArray
     * @param $source
     */
    private function overrideConfigSource(&$configArray, $source)
    {
        $source = realpath($source);
        $ins = isset($configArray['finder']['in']) ? $configArray['finder']['in'] : [];
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
                $configArray['finder']['path'] = $path;
            }
        }

        if (is_file($source)) {
            $configArray['finder']['name'] = pathinfo($source, PATHINFO_BASENAME);
        }
    }

    /**
     * Parse input and return config array.
     *
     * @param InputInterface $input
     *
     * @return array array with config, ready to create a config instance
     */
    private function parseInput(InputInterface $input)
    {
        $source = $input->getArgument('source');
        $license = $input->getArgument('license');
        $configArray = [];

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
        $configArray['finder'] = $finder;

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

        $configArray['parameters'] = $params;
        $configArray['license'] = $license;

        return $configArray;
    }

    /**
     * Parse config array from yml and return array of configs parsed.
     * One yml config can contain multiple configuration to process
     * when use multiple finders.
     *
     * @param array  $configArray
     * @param string $workingDir
     *
     * @return array
     */
    private function parseConfigArray($configArray, $workingDir = null)
    {
        $configs = [];
        if (isset($configArray['finder'])) {
            if (!isset($configArray['license_content'])) {
                $license = isset($configArray['license']) ? $configArray['license'] : 'default';
                //resolve absolute path
                if (file_exists($workingDir.DIRECTORY_SEPARATOR.$license)) {
                    $license = $workingDir.DIRECTORY_SEPARATOR.$license;
                }
                $configArray['license'] = $license;
            }

            if (isset($configArray['finder']['in'])) {
                $inArray = [];
                foreach ((array) $configArray['finder']['in'] as $in) {
                    $inArray[] = $workingDir.DIRECTORY_SEPARATOR.$in;
                }
                $configArray['finder']['in'] = $inArray;
            }

            if (!isset($configArray['finder']['name'])) {
                $configArray['finder']['name'] = '*.php';
            }

            return [$configArray];
        } elseif (isset($configArray['finders'])) {
            foreach ($configArray['finders'] as $finder) {
                $config = array_merge($configArray, ['finder' => $finder]);
                unset($config['finders']);
                $configs = array_merge($configs, $this->parseConfigArray($config, $workingDir));
            }
        }

        return $configs;
    }
}
