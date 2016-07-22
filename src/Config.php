<?php

/*
 * LICENSE: This file is subject to the terms and conditions defined in
 * file 'LICENSE', which is part of this source code package.
 * 
 * @copyright 2016 Copyright(c) - All rights reserved.
 * 
 * @author Rafael SR <https://github.com/rafrsr>
 * @package Licenser
 * @version 1.0.0-alpha
 */

namespace Rafrsr\Licenser;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Yaml\Yaml;

/**
 * Config
 *
 * @author RafaelSR <https://github.com/rafrsr>
 */
class Config
{
    /**
     * @var string
     */
    protected $license;

    /**
     * @var Finder
     */
    protected $finder;

    /**
     * @var array
     */
    protected $parameters = [];

    /**
     * create
     *
     * @return Config
     */
    public static function create()
    {
        return new static();
    }

    /**
     * Create config from console input
     *
     * @param InputInterface $input
     *
     * @return $this
     */
    public static function createFromInput(InputInterface $input)
    {
        $source = $input->getArgument('source');
        $license = $input->getArgument('license');

        if (file_exists($source)) {
            $finder = Finder::create();
            if (is_dir($source)) {
                $finder->name('*.php')->in(realpath($source));
            } else {
                $file = new File($source);
                $finder
                    ->name($file->getFilename())
                    ->depth(0)
                    ->in($file->getPath());
            }
        } else {
            throw new FileNotFoundException($source);
        }

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

        $originLicense = $license;
        if (!file_exists($license)) {
            $license = self::resolveBuildInLicense($license);
        }

        if (!file_exists($license)) {
            throw new \InvalidArgumentException(sprintf('Invalid license file "%s"', $originLicense));
        }

        $rawLicense = file_get_contents($license);

        $config = Config::create()
            ->setParameters($params)
            ->setLicense($rawLicense)
            ->setFinder($finder);

        return $config;
    }

    /**
     * Create config from yml input
     *
     * @param string $ymlFile
     *
     * @return Config
     */
    public static function createFromYml($ymlFile)
    {
        $config = new static();
        $file = new File(realpath($ymlFile));
        $workingDir = $file->getPath();

        $yamlConfig = file_get_contents($file->getPathname());
        $arrayConfig = Yaml::parse($yamlConfig);

        if (isset($arrayConfig['license_content'])) {
            $license = $arrayConfig['license_content'];
        } else {
            $license = isset($arrayConfig['license']) ? $arrayConfig['license'] : 'default';
            if (file_exists($workingDir.DIRECTORY_SEPARATOR.$license)) {
                $license = file_get_contents($workingDir.DIRECTORY_SEPARATOR.$license);
            } elseif (file_exists(self::resolveBuildInLicense($license))) {
                $license = file_get_contents(self::resolveBuildInLicense($license));
            } else {
                throw new FileNotFoundException($license);
            }
        }

        $config->setLicense($license);

        foreach ($arrayConfig['parameters'] as &$parameter) {
            //try to resolve constants
            if (strpos($parameter, '@') === 0 && defined(substr($parameter, 1))) {
                $parameter = constant(substr($parameter, 1));
            }
        }

        $config->setParameters(isset($arrayConfig['parameters']) ? $arrayConfig['parameters'] : []);

        $finder = new Finder();
        if (isset($arrayConfig['finder']['in'])) {
            foreach ((array) $arrayConfig['finder']['in'] as $in) {
                $finder->in($workingDir.DIRECTORY_SEPARATOR.$in);
            }
        } else {
            $finder->in($workingDir);
        }

        if (isset($arrayConfig['finder']['name'])) {
            foreach ((array) $arrayConfig['finder']['name'] as $name) {
                $finder->name($name);
            }
        } else {
            $finder->name('*.php');
        }
        $config->setFinder($finder);

        return $config;
    }

    /**
     * @return string
     */
    public function getLicense()
    {
        return $this->license;
    }

    /**
     * @param string $license
     *
     * @return $this
     */
    public function setLicense($license)
    {
        $this->license = $license;

        return $this;
    }

    /**
     * @return Finder
     */
    public function getFinder()
    {
        return $this->finder;
    }

    /**
     * @param Finder $finder
     *
     * @return $this
     */
    public function setFinder($finder)
    {
        $this->finder = $finder;

        return $this;
    }

    /**
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * @param array $parameters
     *
     * @return $this
     */
    public function setParameters(array $parameters)
    {
        $this->parameters = $parameters;

        return $this;
    }

    /**
     * getParameter
     *
     * @param string $key
     *
     * @return mixed
     */
    public function getParameter($key)
    {
        return $this->parameters[$key];
    }

    /**
     * hasParameter
     *
     * @param string $key
     *
     * @return bool
     */
    public function hasParameter($key)
    {
        return array_key_exists($key, $this->parameters);
    }

    /**
     * Resolve local license full path for passed build in license name
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

        return null;
    }
}
