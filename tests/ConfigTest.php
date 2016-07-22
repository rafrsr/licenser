<?php

/**
 * LICENSE: This file is subject to the terms and conditions defined in
 * file 'LICENSE', which is part of this source code package.
 *
 * @copyright 2016 Copyright(c) - All rights reserved.
 */

namespace Rafrsr\Licenser\Tests;

use Rafrsr\Licenser\Config;
use Symfony\Component\Finder\Finder;

class ConfigTest extends \PHPUnit_Framework_TestCase
{
    public function testSetGetFinder()
    {
        $finder = Finder::create();
        $config = Config::create()->setFinder($finder);

        self::assertEquals($finder, $config->getFinder());
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
