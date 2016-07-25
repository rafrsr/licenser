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

namespace Rafrsr\Licenser\Tests;

use Rafrsr\Licenser\Command\LicenserCommand;
use Rafrsr\Licenser\Config;
use Rafrsr\Licenser\Licenser;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

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

    public function testCreateFromYmlBasic()
    {
        $config = Config::create()
            ->setLicense(file_get_contents(implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'src', 'licenses', 'default'])))
            ->setFinder(Finder::create()->name('*.php')->in(realpath(sys_get_temp_dir().DIRECTORY_SEPARATOR.'licenser')));

        $yml = $this->buildYml(
            [
                'finder' => [
                        'in' => 'licenser',
                    ],
            ]
        );

        self::assertEquals($config, Config::createFromYml($yml));
    }

    public function testCreateFromYmlCustomLicense()
    {
        $config = Config::create()
            ->setLicense(file_get_contents($this->fixturesDir.DIRECTORY_SEPARATOR.'license'))
            ->setFinder(Finder::create()->name('*.php')->in(realpath(sys_get_temp_dir().DIRECTORY_SEPARATOR.'licenser')));

        $yml = $this->buildYml(
            [
                'finder' => [
                        'in' => 'licenser',
                    ],
                'license' => 'licenser/license',
            ]
        );

        self::assertEquals($config, Config::createFromYml($yml));
    }

    public function testCreateFromYmlWithParameters()
    {
        $config = Config::create()
            ->setLicense(file_get_contents(implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'src', 'licenses', 'default'])))
            ->setFinder(Finder::create()->name('*.php')->in(realpath(sys_get_temp_dir().DIRECTORY_SEPARATOR.'licenser')))
            ->setParameters(
                [
                    'name' => 'Author Name',
                    'version' => Licenser::VERSION,
                ]
            );

        $yml = $this->buildYml(
            [
                'finder' => [
                        'in' => 'licenser',
                    ],
                'parameters' => [
                    'name' => 'Author Name',
                    'version' => '@Rafrsr\Licenser\Licenser::VERSION',
                ],
            ]
        );

        self::assertEquals($config, Config::createFromYml($yml));
    }

    public function testCreateFromYmlWithLicenseContent()
    {
        $license = "Custom License Content \n (c) CopyRight";
        $config = Config::create()
            ->setLicense($license)
            ->setFinder(Finder::create()->name('*.php')->in(realpath(sys_get_temp_dir().DIRECTORY_SEPARATOR.'licenser')));

        $yml = $this->buildYml(
            [
                'finder' => [
                        'in' => 'licenser',
                    ],
                'license_content' => $license,
            ]
        );

        self::assertEquals($config, Config::createFromYml($yml));
    }

    public function testCreateFromYmlWithInvalidLicense()
    {
        $yml = $this->buildYml(
            [
                'finder' => [
                        'in' => 'licenser',
                    ],
                'license' => 'my_license',
            ]
        );

        self::setExpectedExceptionRegExp(\InvalidArgumentException::class, '/Invalid license file "my_license"/');
        Config::createFromYml($yml);
    }

    public function testCreateFromYmlCustomizeFinder()
    {
        $fileSystem = new Filesystem();
        $fileSystem->mkdir(sys_get_temp_dir().DIRECTORY_SEPARATOR.'other');

        $config = Config::create()
            ->setLicense(file_get_contents(implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'src', 'licenses', 'default'])))
            ->setFinder(
                Finder::create()
                    ->name('*.php')
                    ->name('*.php4')
                    ->in(
                        [
                            realpath(sys_get_temp_dir().DIRECTORY_SEPARATOR.'licenser'),
                            realpath(sys_get_temp_dir().DIRECTORY_SEPARATOR.'other'),
                        ]
                    )
                    ->notPath('notPath')
                    ->contains('license')
                    ->path('dir')
                    ->path('other')
                    ->exclude('*Test.php')
                    ->exclude('config.php')
                    ->ignoreDotFiles(true)
                    ->followLinks()
                    ->depth(2)
                    ->notContains('license')
                    ->size('>= 1K')
                    ->date('yesterday')
            );

        $yml = $this->buildYml(
            [
                'finder' => [
                        'in' => [
                            'licenser',
                            'other',
                        ],
                        'name' => [
                            '*.php',
                            '*.php4',
                        ],
                        'notPath' => 'notPath',
                        'contains' => 'license',
                        'path' => ['dir', 'other'],
                        'exclude' => ['*Test.php', 'config.php'],
                        'ignoreDotFiles' => true,
                        'followLinks' => true,
                        'depth' => 2,
                        'notContains' => 'license',
                        'size' => '>= 1K',
                        'date' => 'yesterday',
                    ],
            ]
        );

        self::assertEquals($config, Config::createFromYml($yml));
    }

    public function testCreateFromYmlCustomizeFinderInvalidMethod()
    {
        $yml = $this->buildYml(
            [
                'finder' => [
                        'in' => [
                            'licenser',
                            'other',
                        ],
                        'find' => '*',
                    ],
            ]
        );
        self::setExpectedExceptionRegExp(\InvalidArgumentException::class, '/The method "Finder::find" does not exist/');
        Config::createFromYml($yml);
    }

    public function testCreateFromYmlWithMissingFinderIn()
    {
        $yml = $this->buildYml([]);

        self::setExpectedExceptionRegExp(\LogicException::class, '/Invalid configuration, value of "finder.in"/');
        Config::createFromYml($yml);
    }

    protected function buildInput($array)
    {
        $input = new ArrayInput($array);
        $command = new LicenserCommand();
        $input->bind($command->getDefinition());

        return $input;
    }

    protected function buildYml($array)
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'licenser');
        file_put_contents($tempFile, Yaml::dump($array));

        return $tempFile;
    }
}
