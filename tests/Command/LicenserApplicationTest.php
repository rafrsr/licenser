<?php

/*
 * LICENSE: This file is subject to the terms and conditions defined in
 * file 'LICENSE', which is part of this source code package.
 *
 * @copyright 2016 Copyright(c) - All rights reserved.
 *
 * @author Rafael SR <https://github.com/rafrsr>
 * @package Licenser
 * @version 1.0.3
 */

namespace Rafrsr\Licenser\Tests\Command;

use Rafrsr\Licenser\Command\LicenserApplication;
use Rafrsr\Licenser\Licenser;

class LicenserApplicationTest extends \PHPUnit_Framework_TestCase
{
    public function testConstruct()
    {
        $app = new LicenserApplication();
        self::assertEquals(Licenser::VERSION, $app->getVersion());
        self::assertEquals('Licenser', $app->getName());
    }

    public function testDefinitions()
    {
        $app = new LicenserApplication();
        self::assertEmpty($app->getDefinition()->getArguments());
    }
}
