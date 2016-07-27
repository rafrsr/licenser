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

use Rafrsr\Licenser\Config;
use Rafrsr\Licenser\FinderBuilder;

class ConfigTest extends \PHPUnit_Framework_TestCase
{
    use LicenseTesterSetUpTrait;

    public function testSetGetFinder()
    {
        $finder = FinderBuilder::create();
        $config = Config::create()->setFinderBuilder($finder);

        self::assertEquals($finder, $config->getFinderBuilder());
    }

    public function testSetGetLicense()
    {
        $license = 'license content';
        $config = Config::create()->setLicense($license);

        self::assertEquals($license, $config->getLicense());
    }

    public function testSetGetParameters()
    {
        $parameters = [
            'author' => 'AuthorName',
            'version' => 'v1.0',
        ];
        $config = Config::create()->setParameters($parameters);

        self::assertEquals($parameters, $config->getParameters());
        self::assertEquals('v1.0', $config->getParameter('version'));
        self::assertTrue($config->hasParameter('version'));
        self::assertFalse($config->hasParameter('name'));
    }
}
