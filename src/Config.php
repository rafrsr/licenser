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

use Symfony\Component\Finder\Finder;

/**
 * Config.
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
     * create.
     *
     * @return Config
     */
    public static function create()
    {
        return new static();
    }

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
     * @return $this
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

        if (!isset($configArray['finder']['name'])) {
            $configArray['finder']['name'] = '*.php'; //default file types
        }

        if (!isset($configArray['finder']['in'])) {
            throw new \LogicException('Invalid configuration, at least one source is required to locate files.');
        }

        $finder = new Finder();
        if (isset($configArray['finder'])) {
            foreach ($configArray['finder'] as $method => $arguments) {
                if (false === method_exists($finder, $method)) {
                    throw new \InvalidArgumentException(
                        sprintf(
                            'The method "Finder::%s" does not exist.',
                            $method
                        )
                    );
                }

                $arguments = (array) $arguments;

                foreach ($arguments as $argument) {
                    $finder->$method($argument);
                }
            }
        }

        $config = self::create()
            ->setLicense($license)
            ->setParameters($parameters)
            ->setFinder($finder);

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
     * getParameter.
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
     * hasParameter.
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
    }
}
