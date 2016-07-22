<?php

/**
 * LICENSE: This file is subject to the terms and conditions defined in
 * file 'LICENSE', which is part of this source code package.
 *
 * @copyright 2016 Copyright(c) - All rights reserved.
 */

namespace Rafrsr\Licenser\Tests\Command;

use Rafrsr\Licenser\Command\LicenserCommand;
use Rafrsr\Licenser\Config;
use Rafrsr\Licenser\Licenser;
use Rafrsr\Licenser\Tests\LicenseTesterSetUpTrait;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Tests\Fixtures\DummyOutput;
use Symfony\Component\Finder\Finder;

class LicenserCommandTest extends \PHPUnit_Framework_TestCase
{
    use LicenseTesterSetUpTrait;

    public function testBasicLicenser()
    {
        $licenser = self::getMockBuilder(Licenser::class)->disableOriginalConstructor()->getMock();
        $licenser->expects(self::once())->method('process')->with(Licenser::MODE_NORMAL);

        $config = Config::create()
            ->setLicense(file_get_contents(implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', 'src', 'licenses', 'default'])))
            ->setFinder(Finder::create()->name('*.php')->in(realpath($this->tempDir)));

        $command = self::getMockBuilder(LicenserCommand::class)->setMethods(['buildLicenser'])->getMock();
        $command->expects(self::once())->method('buildLicenser')->with($config)->willReturn($licenser);

        $input = new ArrayInput(
            [
                'source' => realpath($this->tempDir),
            ]
        );
        $output = new DummyOutput();
        $command->run($input, $output);
    }

    public function testLicenserDryRun()
    {
        $licenser = self::getMockBuilder(Licenser::class)->disableOriginalConstructor()->getMock();
        $licenser->expects(self::once())->method('process')->with(Licenser::MODE_DRY_RUN);

        $command = self::getMockBuilder(LicenserCommand::class)->setMethods(['buildLicenser'])->getMock();
        $command->expects(self::once())->method('buildLicenser')->willReturn($licenser);

        $input = new ArrayInput(
            [
                'source' => realpath($this->tempDir),
                '--dry-run' => true,
            ]
        );
        $output = new DummyOutput();
        $command->run($input, $output);
    }

    public function testLicenserCheckOnly()
    {
        $licenser = self::getMockBuilder(Licenser::class)->disableOriginalConstructor()->getMock();
        $licenser->expects(self::once())->method('process')->with(Licenser::MODE_CHECK_ONLY);

        $command = self::getMockBuilder(LicenserCommand::class)->setMethods(['buildLicenser'])->getMock();
        $command->expects(self::once())->method('buildLicenser')->willReturn($licenser);

        $input = new ArrayInput(
            [
                'source' => realpath($this->tempDir),
                '--check-only' => true,
            ]
        );
        $output = new DummyOutput();
        $command->run($input, $output);
    }
}
