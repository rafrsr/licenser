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

use Symfony\Component\Console\Input\InputInterface;

/**
 * Resolve dynamic parameters in command line
 *
 * e.g. --finder=name:*.php --finder=name:*.js --finder=path:src
 *
 * will be resolved:
 *
 * [
 *  'name' => [
 *          '*.php',
 *          '*.js',
 *   ],
 *  'path' => 'src'
 * ]
 */
class DynamicParameterResolver
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $separator;

    /**
     * @var array
     */
    protected $params = [];

    /**
     * DynamicParameterResolver constructor.
     *
     * @param string $name
     * @param string $separator
     */
    public function __construct($name, $separator)
    {
        $this->name = $name;
        $this->separator = $separator;
    }

    /**
     * Create new resolver instance
     *
     * @param string $name
     * @param string $separator
     *
     * @return static
     */
    public static function create($name, $separator = ':')
    {
        return new static($name, $separator);
    }

    /**
     * @param InputInterface $input
     *
     * @return array
     */
    public function resolve(InputInterface $input)
    {
        if ($input->getOption($this->name)) {
            foreach ($input->getOption($this->name) as $param) {
                if (strpos($param, $this->separator) !== false) {
                    list($name, $value) = explode($this->separator, $param, 2);
                    $this->setValue($name, $value);
                } else {
                    $msg = sprintf('Invalid parameter "%s", should have the format "name%svalue"', $param, $this->separator);
                    throw new \InvalidArgumentException($msg);
                }
            }
        }

        return $this->params;
    }

    /**
     * Set parameter value
     *
     * @param string $name
     * @param mixed  $value
     */
    private function setValue($name, $value)
    {
        if (array_key_exists($name, $this->params)) {
            if (!is_array($this->params[$name])) {
                $this->params[$name] = [
                    $this->params[$name],
                    $value,
                ];
            } else {
                $this->params[$name][] = $value;
            }
        } else {
            $this->params[$name] = $value;
        }
    }
}
