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
 * This builder allow create a Finder using a array of settings and
 * override or clear any property in existent finder.
 *
 * @method $this in($dirs)
 * @method $this name($pattern)
 * @method $this notName($pattern)
 * @method $this depth($level)
 * @method $this date($date)
 * @method $this contains($pattern)
 * @method $this notContains($pattern)
 * @method $this path($pattern)
 * @method $this notPath($pattern)
 * @method $this size($size)
 * @method $this exclude($dirs)
 * @method $this ignoreDotFiles($ignoreDotFiles)
 * @method $this ignoreVCS($ignoreVCS)
 * @method $this addVCSPattern($pattern)
 * @method $this clearIn()
 * @method $this clearName()
 * @method $this clearNotName()
 * @method $this clearDepth()
 * @method $this clearDate()
 * @method $this clearContains()
 * @method $this clearNotContains()
 * @method $this clearPath()
 * @method $this clearNotPath()
 * @method $this clearSize()
 * @method $this clearExclude()
 * @method $this clearIgnoreDotFiles()
 * @method $this clearIgnoreVCS()
 * @method $this clearAddVCSPattern()
 * @method array getIn()
 * @method array getName()
 * @method array getNotName()
 * @method array getDepth()
 * @method array getDate()
 * @method array getContains()
 * @method array getNotContains()
 * @method array getPath()
 * @method array getNotPath()
 * @method array getSize()
 * @method array getExclude()
 * @method bool getIgnoreDotFiles()
 * @method bool getIgnoreVCS()
 * @method bool getAddVCSPattern()
 */
class FinderBuilder
{
    protected $finderSettings = [];

    /**
     * @param array $finderSettings
     */
    final public function __construct(array $finderSettings = [])
    {
        $this->finderSettings = $finderSettings;
    }

    /**
     * @param array $finderSettings array of finder settings to create the builder
     *                              e.g.FinderBuilder::create(['in'=>'path/to/files'])->build()
     *
     * @return FinderBuilder
     */
    public static function create(array $finderSettings = [])
    {
        return new self($finderSettings);
    }

    /**
     * {@inheritdoc}
     */
    public function __call($name, $arguments)
    {
        if (strpos($name, 'clear') === 0) {
            $name = lcfirst(str_replace('clear', null, $name));
            $this->finderSettings[$name] = [];
        } elseif (strpos($name, 'get') === 0) {
            $name = lcfirst(str_replace('get', null, $name));

            return $this->finderSettings[$name];
        } else {
            $this->finderSettings[$name][] = array_values($arguments)[0];
        }

        return $this;
    }

    /**
     * @throws \BadFunctionCallException if some method not exist in the finder
     *
     * @return Finder
     */
    public function build()
    {
        $finder = new Finder();
        foreach ($this->finderSettings as $method => $arguments) {
            if (false === method_exists($finder, $method)) {
                throw new \BadFunctionCallException(
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

        return $finder;
    }
}
