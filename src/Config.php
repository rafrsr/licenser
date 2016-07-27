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
     * @var FinderBuilder
     */
    protected $finderBuilder;

    /**
     * @var array
     */
    protected $parameters = [];

    /**
     * Config constructor.
     */
    public function __construct()
    {
        $this->finderBuilder = FinderBuilder::create();
    }

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
     * @return FinderBuilder
     */
    public function getFinderBuilder()
    {
        return $this->finderBuilder;
    }

    /**
     * @param FinderBuilder $finderBuilder
     *
     * @return $this
     */
    public function setFinderBuilder(FinderBuilder $finderBuilder)
    {
        $this->finderBuilder = $finderBuilder;

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
}
