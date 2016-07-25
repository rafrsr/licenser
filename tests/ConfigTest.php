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

namespace Rafrsr\Licenser\tests;

use Rafrsr\Licenser\Config;
use Rafrsr\Licenser\Licenser;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

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

    public function testCreateFromArrayBasic()
    {
        $config = Config::create()
            ->setLicense(file_get_contents(implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'src', 'licenses', 'default'])))
            ->setFinder(Finder::create()->name('*.php')->in(realpath($this->tempDir)));

        $array = [
            'finder' => [
                'in' => realpath($this->tempDir),
            ],
        ];

        self::assertEquals($config, Config::createFromArray($array));
    }

    public function testCreateFromArrayCustomLicense()
    {
        $config = Config::create()
            ->setLicense(file_get_contents($this->fixturesDir.DIRECTORY_SEPARATOR.'license'))
            ->setFinder(Finder::create()->name('*.php')->in(realpath($this->tempDir)));

        $array = [
            'finder' => [
                'in' => realpath($this->tempDir),
            ],
            'license' => realpath($this->fixturesDir.DIRECTORY_SEPARATOR.'license'),
        ];
        self::assertEquals($config, Config::createFromArray($array));
    }

    public function testCreateFromArrayWithParameters()
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

        $array = [
            'finder' => [
                'in' => realpath(sys_get_temp_dir().DIRECTORY_SEPARATOR.'licenser'),
            ],
            'parameters' => [
                'name' => 'Author Name',
                'version' => '@Rafrsr\Licenser\Licenser::VERSION',
            ],
        ];

        self::assertEquals($config, Config::createFromArray($array));
    }

    public function testCreateFromArrayWithLicenseContent()
    {
        $license = "Custom License Content \n (c) CopyRight";
        $config = Config::create()
            ->setLicense($license)
            ->setFinder(Finder::create()->name('*.php')->in(realpath(sys_get_temp_dir().DIRECTORY_SEPARATOR.'licenser')));

        $array = [
            'finder' => [
                'in' => realpath(sys_get_temp_dir().DIRECTORY_SEPARATOR.'licenser'),
            ],
            'license_content' => $license,
        ];

        self::assertEquals($config, Config::createFromArray($array));
    }

    public function testCreateFromArrayWithInvalidLicense()
    {
        $array = [
            'finder' => [
                'in' => 'licenser',
            ],
            'license' => 'my_license',
        ];

        self::setExpectedExceptionRegExp(\InvalidArgumentException::class, '/Invalid license file "my_license"/');
        Config::createFromArray($array);
    }

    public function testCreateFromArrayCustomizeFinder()
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

        $array = [
            'finder' => [
                'in' => [
                    realpath(sys_get_temp_dir().DIRECTORY_SEPARATOR.'licenser'),
                    realpath(sys_get_temp_dir().DIRECTORY_SEPARATOR.'other'),
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
        ];

        self::assertEquals($config, Config::createFromArray($array));
    }

    public function testCreateFromArrayCustomizeFinderInvalidMethod()
    {
        $array = [
            'finder' => [
                'in' => [
                    realpath(sys_get_temp_dir().DIRECTORY_SEPARATOR.'licenser'),
                ],
                'find' => '*',
            ],
        ];
        self::setExpectedExceptionRegExp(\InvalidArgumentException::class, '/The method "Finder::find" does not exist/');
        Config::createFromArray($array);
    }

    public function testCreateFromYmlWithMissingFinderIn()
    {
        self::setExpectedExceptionRegExp(\LogicException::class, '/Invalid configuration, at least one source is required to locate files/');
        Config::createFromArray([]);
    }
}
