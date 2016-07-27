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

namespace Rafrsr\Licenser\Tests;

use Rafrsr\Licenser\FinderBuilder;
use Symfony\Component\Finder\Finder;

class FinderBuilderTest extends \PHPUnit_Framework_TestCase
{
    public function testFinderBuilder()
    {
        $finder = FinderBuilder::create()
            ->in(sys_get_temp_dir())
            ->name('*.php')
            ->notName('*.js')
            ->path('/plugin')
            ->notPath('/vendor')
            ->exclude('/js')
            ->build();

        $expectedFinder = new Finder();
        $expectedFinder->in(sys_get_temp_dir())
            ->name('*.php')
            ->notName('*.js')
            ->path('/plugin')
            ->notPath('/vendor')
            ->exclude('/js');

        self::assertEquals($expectedFinder, $finder);
    }

    public function testFinderBuilderClear()
    {
        $finder = FinderBuilder::create()
            ->in(sys_get_temp_dir())
            ->name('*.php')
            ->notName('*.js')
            ->path('/plugin')
            ->notPath('/vendor')
            ->exclude('/js')
            ->clearPath()
            ->clearNotPath()
            ->clearExclude()
            ->build();

        $expectedFinder = new Finder();
        $expectedFinder->in(sys_get_temp_dir())
            ->name('*.php')
            ->notName('*.js');

        self::assertEquals($expectedFinder, $finder);
    }

    public function testFinderBuilderInvalidMethod()
    {
        self::expectException(\BadFunctionCallException::class);
        FinderBuilder::create()
            ->in(sys_get_temp_dir())
            ->include('*.cls')
            ->build();
    }
}
