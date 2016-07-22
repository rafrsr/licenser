<?php

/**
 * LICENSE: This file is subject to the terms and conditions defined in
 * file 'LICENSE', which is part of this source code package.
 *
 * @copyright 2016 Copyright(c) - All rights reserved.
 */

namespace Rafrsr\Licenser\Tests;

use Rafrsr\Licenser\Command\LicenserCommand;
use Rafrsr\Licenser\Config;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;

class ConfigTest extends \PHPUnit_Framework_TestCase
{
    use LicenseTesterSetUpTrait;

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

    public function testCreateFromInputBasic()
    {
        $config = Config::create()
            ->setLicense(file_get_contents(implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'src', 'licenses', 'default'])))
            ->setFinder(Finder::create()->name('*.php')->in(realpath($this->tempDir)));

        $input = $this->buildInput(
            [
                'source' => realpath($this->tempDir),
            ]
        );

        self::assertEquals($config, Config::createFromInput($input));
    }

    public function tesetCreateFromInputCustomLicense()
    {
        $config = Config::create()
            ->setLicense(file_get_contents($this->fixturesDir.DIRECTORY_SEPARATOR.'license'))
            ->setFinder(Finder::create()->name('*.php')->in(realpath($this->tempDir)));

        $input = $this->buildInput(
            [
                'source' => realpath($this->tempDir),
                'license' => realpath($this->fixturesDir.DIRECTORY_SEPARATOR.'license'),
            ]
        );
        self::assertEquals($config, Config::createFromInput($input));
    }

    public function testCreateFromInputWithParameters()
    {
        $config = Config::create()
            ->setLicense(file_get_contents(implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'src', 'licenses', 'default'])))
            ->setFinder(Finder::create()->name('*.php')->in(realpath($this->tempDir)))
            ->setParameters(
                [
                    'author' => 'AuthorName',
                    'version' => 'v1.0',
                ]
            );

        $input = $this->buildInput(
            [
                'source' => realpath($this->tempDir),
                '-p' => [
                    'author:AuthorName',
                    'version:v1.0',
                ],
            ]
        );
        self::assertEquals($config, Config::createFromInput($input));
    }

    public function testCreateFromInputOneFile()
    {
        $config = Config::create()
            ->setLicense(file_get_contents(implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'src', 'licenses', 'default'])))
            ->setFinder(Finder::create()->name('file.php')->in(realpath($this->tempDir))->depth(0));

        $input = $this->buildInput(
            [
                'source' => realpath($this->tempDir.DIRECTORY_SEPARATOR.'file.php'),
            ]
        );
        self::assertEquals($config, Config::createFromInput($input));
    }

    public function testCreateFromInputForInvalidFile()
    {
        $input = $this->buildInput(
            [
                'source' => realpath($this->tempDir.DIRECTORY_SEPARATOR.'file_none.php'),
            ]
        );
        self::expectException(FileNotFoundException::class);
        Config::createFromInput($input);
    }

    public function testCreateFromInputInvalidParameter()
    {
        $input = $this->buildInput(
            [
                'source' => realpath($this->tempDir.DIRECTORY_SEPARATOR.'file.php'),
                '-p' => [
                    'author' => 'AuthorName',
                ],
            ]
        );
        self::setExpectedExceptionRegExp(\InvalidArgumentException::class, '/Invalid parameter "AuthorName"/');
        Config::createFromInput($input);
    }

    public function testCreateFromInputInvalidLicenseFile()
    {
        $input = $this->buildInput(
            [
                'source' => realpath($this->tempDir.DIRECTORY_SEPARATOR.'file.php'),
                'license' => 'my_license',
            ]
        );
        self::setExpectedExceptionRegExp(\InvalidArgumentException::class, '/Invalid license file "my_license"/');
        Config::createFromInput($input);
    }

    protected function buildInput($array)
    {
        $input = new ArrayInput($array);
        $command = new LicenserCommand();
        $input->bind($command->getDefinition());

        return $input;
    }
}
