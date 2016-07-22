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
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;

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

    public function testLicenserWithCustomLicense()
    {
        $licenser = self::getMockBuilder(Licenser::class)->disableOriginalConstructor()->getMock();
        $licenser->expects(self::once())->method('process')->with(Licenser::MODE_NORMAL);

        $config = Config::create()
            ->setLicense(file_get_contents($this->fixturesDir.DIRECTORY_SEPARATOR.'license'))
            ->setFinder(Finder::create()->name('*.php')->in(realpath($this->tempDir)));

        $command = self::getMockBuilder(LicenserCommand::class)->setMethods(['buildLicenser'])->getMock();
        $command->expects(self::once())->method('buildLicenser')->with($config)->willReturn($licenser);

        $input = new ArrayInput(
            [
                'source' => realpath($this->tempDir),
                'license' => realpath($this->fixturesDir.DIRECTORY_SEPARATOR.'license'),
            ]
        );
        $output = new DummyOutput();
        $command->run($input, $output);
    }

    public function testLicenserWithParameters()
    {
        $licenser = self::getMockBuilder(Licenser::class)->disableOriginalConstructor()->getMock();
        $licenser->expects(self::once())->method('process')->with(Licenser::MODE_NORMAL);

        $config = Config::create()
            ->setLicense(file_get_contents(implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', 'src', 'licenses', 'default'])))
            ->setFinder(Finder::create()->name('*.php')->in(realpath($this->tempDir)))
            ->setParameters(
                [
                    'author' => 'AuthorName',
                    'version' => 'v1.0',
                ]
            );

        $command = self::getMockBuilder(LicenserCommand::class)->setMethods(['buildLicenser'])->getMock();
        $command->expects(self::once())->method('buildLicenser')->with($config)->willReturn($licenser);

        $input = new ArrayInput(
            [
                'source' => realpath($this->tempDir),
                '-p' => [
                    'author:AuthorName',
                    'version:v1.0',
                ],
            ]
        );
        $output = new DummyOutput();
        $command->run($input, $output);
    }

    public function testLicenserForOneFile()
    {
        $licenser = self::getMockBuilder(Licenser::class)->disableOriginalConstructor()->getMock();
        $licenser->expects(self::once())->method('process')->with(Licenser::MODE_NORMAL);

        $config = Config::create()
            ->setLicense(file_get_contents(implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', 'src', 'licenses', 'default'])))
            ->setFinder(Finder::create()->name('file.php')->in(realpath($this->tempDir))->depth(0));

        $command = self::getMockBuilder(LicenserCommand::class)->setMethods(['buildLicenser'])->getMock();
        $command->expects(self::once())->method('buildLicenser')->with($config)->willReturn($licenser);

        $input = new ArrayInput(
            [
                'source' => realpath($this->tempDir.DIRECTORY_SEPARATOR.'file.php'),
            ]
        );
        $output = new DummyOutput();
        $command->run($input, $output);
    }

    public function testLicenserForInvalidFile()
    {
        $command = self::getMockBuilder(LicenserCommand::class)->setMethods(['buildLicenser'])->getMock();
        $input = new ArrayInput(
            [
                'source' => realpath($this->tempDir.DIRECTORY_SEPARATOR.'file_none.php'),
            ]
        );
        self::expectException(FileNotFoundException::class);
        $output = new DummyOutput();
        $command->run($input, $output);
    }

    public function testLicenserInvalidParameter()
    {
        $command = self::getMockBuilder(LicenserCommand::class)->setMethods(['buildLicenser'])->getMock();
        $input = new ArrayInput(
            [
                'source' => realpath($this->tempDir.DIRECTORY_SEPARATOR.'file.php'),
                '-p' => [
                    'author' => 'AuthorName',
                ],
            ]
        );
        self::setExpectedExceptionRegExp(\InvalidArgumentException::class, '/Invalid parameter "AuthorName"/');
        $output = new DummyOutput();
        $command->run($input, $output);
    }

    public function testLicenserInvalidLicenseFile()
    {
        $command = self::getMockBuilder(LicenserCommand::class)->setMethods(['buildLicenser'])->getMock();
        $input = new ArrayInput(
            [
                'source' => realpath($this->tempDir.DIRECTORY_SEPARATOR.'file.php'),
                'license' => 'my_license',
            ]
        );
        self::setExpectedExceptionRegExp(\InvalidArgumentException::class, '/Invalid license file "my_license"/');
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
