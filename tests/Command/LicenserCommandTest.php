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

namespace Rafrsr\Licenser\Tests\Command;

use Rafrsr\Licenser\Command\LicenserApplication;
use Rafrsr\Licenser\Command\LicenserCommand;
use Rafrsr\Licenser\Config;
use Rafrsr\Licenser\FinderBuilder;
use Rafrsr\Licenser\Licenser;
use Rafrsr\Licenser\Tests\LicenseTesterSetUpTrait;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Console\Tests\Fixtures\DummyOutput;
use Symfony\Component\Yaml\Yaml;

class LicenserCommandTest extends \PHPUnit_Framework_TestCase
{
    use LicenseTesterSetUpTrait;

    public function testFullFunctionalCommand()
    {
        $application = new LicenserApplication();

        $command = $application->find('licenser');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['source' => realpath($this->tempDir)]);

        self::assertContains('4 file(s) has been processed', $commandTester->getDisplay());
    }

    public function testFullFunctionalCommandCheckOnly()
    {
        $application = new LicenserApplication();

        $command = $application->find('licenser');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['source' => realpath($this->tempDir), '--check-only' => true]);
        self::assertContains('[WARN] 4 file(s) should be updated.', $commandTester->getDisplay());
        self::assertContains('4 file(s) has been processed', $commandTester->getDisplay());
        self::assertEquals(1, $commandTester->getStatusCode());
    }

    public function testBasicLicenserFromConfig()
    {
        $licenser = self::getMockBuilder(Licenser::class)->disableOriginalConstructor()->getMock();
        $licenser->expects(self::once())->method('process')->with(Licenser::MODE_NORMAL);

        $config = Config::create()
            ->setLicense(file_get_contents(implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', 'src', 'licenses', 'default'])))
            ->setFinderBuilder(
                FinderBuilder::create(
                    [
                        'in' => [realpath($this->tempDir)],
                        'name' => '*.php',
                    ]
                )
            );

        $command = self::getMockBuilder(LicenserCommand::class)->setMethods(['buildLicenser'])->getMock();
        $command->expects(self::once())->method('buildLicenser')->with($config)->willReturn($licenser);

        $yamlFile = tempnam(sys_get_temp_dir(), 'licenser');
        $yaml = Yaml::dump(
            [
                'finder' => [
                    'in' => 'licenser',
                ],
            ]
        );
        file_put_contents($yamlFile, $yaml);

        $input = new ArrayInput(
            [
                '--config' => realpath($yamlFile),
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
