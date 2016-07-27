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
use Rafrsr\Licenser\ConfigFactory;
use Rafrsr\Licenser\FinderBuilder;
use Rafrsr\Licenser\Licenser;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

class ConfigFactoryTest extends \PHPUnit_Framework_TestCase
{
    use LicenseTesterSetUpTrait;

    public function testCreateFromArrayBasic()
    {
        $config = Config::create()
            ->setLicense(file_get_contents(implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'src', 'licenses', 'default'])))
            ->setFinderBuilder(
                FinderBuilder::create(
                    [
                        'in' => realpath($this->tempDir),
                        'name' => '*.php',
                    ]
                )
            );

        $array = [
            'finder' => [
                'in' => realpath($this->tempDir),
            ],
        ];

        self::assertEquals($config, ConfigFactory::createFromArray($array));
    }

    public function testCreateFromArrayCustomLicense()
    {
        $config = Config::create()
            ->setLicense(file_get_contents($this->fixturesDir.DIRECTORY_SEPARATOR.'license'))
            ->setFinderBuilder(
                FinderBuilder::create(
                    [
                        'in' => realpath($this->tempDir),
                        'name' => '*.php',
                    ]
                )
            );

        $array = [
            'finder' => [
                'in' => realpath($this->tempDir),
            ],
            'license' => realpath($this->fixturesDir.DIRECTORY_SEPARATOR.'license'),
        ];
        self::assertEquals($config, ConfigFactory::createFromArray($array));
    }

    public function testCreateFromArrayWithParameters()
    {
        $config = Config::create()
            ->setLicense(file_get_contents(implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'src', 'licenses', 'default'])))
            ->setFinderBuilder(
                FinderBuilder::create(
                    [
                        'in' => realpath(sys_get_temp_dir().DIRECTORY_SEPARATOR.'licenser'),
                        'name' => '*.php',
                    ]
                )
            )
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

        self::assertEquals($config, ConfigFactory::createFromArray($array));
    }

    public function testCreateFromArrayWithLicenseContent()
    {
        $license = "Custom License Content \n (c) CopyRight";
        $config = Config::create()
            ->setLicense($license)
            ->setFinderBuilder(
                FinderBuilder::create(
                    [
                        'in' => realpath(sys_get_temp_dir().DIRECTORY_SEPARATOR.'licenser'),
                        'name' => '*.php',
                    ]
                )
            );

        $array = [
            'finder' => [
                'in' => realpath(sys_get_temp_dir().DIRECTORY_SEPARATOR.'licenser'),
            ],
            'license_content' => $license,
        ];

        self::assertEquals($config, ConfigFactory::createFromArray($array));
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
        ConfigFactory::createFromArray($array);
    }

    public function testCreateFromArrayCustomizeFinder()
    {
        $fileSystem = new Filesystem();
        $fileSystem->mkdir(sys_get_temp_dir().DIRECTORY_SEPARATOR.'other');

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

        $config = Config::create()
            ->setLicense(file_get_contents(implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'src', 'licenses', 'default'])))
            ->setFinderBuilder(FinderBuilder::create($array['finder']));

        self::assertEquals($config, ConfigFactory::createFromArray($array));
    }

    public function testCreateFromYmlWithMissingFinderIn()
    {
        self::setExpectedExceptionRegExp(\LogicException::class, '/Invalid configuration, at least one source is required to locate files/');
        ConfigFactory::createFromArray([]);
    }

    public function testCreateFromCommandLine()
    {
        $config = Config::create()
            ->setLicense(file_get_contents(implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'src', 'licenses', 'default'])))
            ->setFinderBuilder(
                FinderBuilder::create(
                    [
                        'in' => realpath($this->tempDir),
                        'name' => '*.php',
                    ]
                )
            );

        $input = $this->buildInput(
            [
                'source' => realpath($this->tempDir),
            ]
        );
        self::assertEquals($config, ConfigFactory::createFromCommandLine($input));
    }

    public function testCreateFromCommandLineToOneFile()
    {
        $config = Config::create()
            ->setLicense(file_get_contents(implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'src', 'licenses', 'default'])))
            ->setFinderBuilder(
                FinderBuilder::create(
                    [
                        'in' => realpath($this->tempDir),
                        'name' => 'file.php',
                        'depth' => 0,
                    ]
                )
            );

        $input = $this->buildInput(
            [
                'source' => $this->tempDir.DIRECTORY_SEPARATOR.'file.php',
            ]
        );
        self::assertEquals($config, ConfigFactory::createFromCommandLine($input));
    }

    public function testCreateFromCommandLineInputParameters()
    {
        $config = Config::create()
            ->setLicense(file_get_contents(implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'src', 'licenses', 'default'])))
            ->setFinderBuilder(
                FinderBuilder::create(
                    [
                        'in' => realpath($this->tempDir),
                        'name' => '*.php',
                    ]
                )
            )
            ->setParameters(['author' => 'AuthorName', 'version' => 'v1.0']);

        $input = $this->buildInput(
            [
                'source' => realpath($this->tempDir),
                '-p' => [
                    'author:AuthorName',
                    'version:v1.0',
                ],
            ]
        );
        self::assertEquals($config, ConfigFactory::createFromCommandLine($input));
    }

    public function testCreateFromCommandLineInvalidParameters()
    {
        $input = $this->buildInput(
            [
                'source' => realpath($this->tempDir),
                '-p' => [
                    'author' => 'AuthorName',
                ],
            ]
        );
        self::setExpectedExceptionRegExp(\InvalidArgumentException::class, '/Invalid parameter "AuthorName"/');
        ConfigFactory::createFromCommandLine($input);
    }

    public function testCreateFromCommandLineInputFinder()
    {
        $config = Config::create()
            ->setLicense(file_get_contents(implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'src', 'licenses', 'default'])))
            ->setFinderBuilder(
                FinderBuilder::create(
                    [
                        'in' => realpath($this->tempDir),
                        'name' => '*.php',
                        'notName' => ['*Test.php'],
                        'exclude' => ['vendor'],
                    ]
                )
            );

        $input = $this->buildInput(
            [
                'source' => realpath($this->tempDir),
                '-f' => [
                    'notName:*Test.php',
                    'exclude:vendor',
                ],
            ]
        );

        self::assertEquals($config, ConfigFactory::createFromCommandLine($input));
    }

    public function testCreateFromCommandLineInvalidFinderOption()
    {
        $input = $this->buildInput(
            [
                'source' => realpath($this->tempDir),
                '-f' => [
                    'exclude' => 'vendor',
                ],
            ]
        );
        self::setExpectedExceptionRegExp(\InvalidArgumentException::class, '/Invalid finder option "vendor"/');

        ConfigFactory::createFromCommandLine($input);
    }

    public function testCreateFromCommandLineInvalidSource()
    {
        $input = $this->buildInput(
            [
                'source' => 'path',
            ]
        );
        self::setExpectedExceptionRegExp(FileNotFoundException::class);

        ConfigFactory::createFromCommandLine($input);
    }

    public function testCreateFromConfigFile()
    {
        $config = Config::create()
            ->setLicense(file_get_contents(implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'src', 'licenses', 'default'])))
            ->setFinderBuilder(
                FinderBuilder::create(
                    [
                        'in' => [realpath($this->tempDir)],
                        'name' => '*.php',
                    ]
                )
            );

        $configFile = $this->buildYml(
            [
                'finder' => [
                    'in' => 'licenser',
                ],
            ]
        );
        self::assertEquals($config, ConfigFactory::createFromConfigFile($configFile)[0]);
    }

    public function testCreateFromConfigFileInvalid()
    {
        $configFile = $this->buildYml([]);
        self::setExpectedExceptionRegExp(\LogicException::class, '/Invalid configuration, configure at least one /');
        ConfigFactory::createFromConfigFile($configFile);
    }

    public function testCreateFromConfigFileInvalidNoSource()
    {
        $configFile = $this->buildYml(['finder' => []]);
        self::setExpectedExceptionRegExp(\LogicException::class, '/Invalid configuration, at least one source is required/');
        ConfigFactory::createFromConfigFile($configFile);
    }

    public function testCreateFromConfigFileResolveLicenseAbosulutePath()
    {
        $fileSystem = new Filesystem();
        $fileSystem->mkdir(sys_get_temp_dir().DIRECTORY_SEPARATOR.'src');
        file_put_contents(sys_get_temp_dir().DIRECTORY_SEPARATOR.'my_license', 'My license content');

        $config = Config::create()
            ->setLicense('My license content')
            ->setFinderBuilder(
                FinderBuilder::create(
                    [
                        'in' => [realpath(sys_get_temp_dir().DIRECTORY_SEPARATOR.'src')],
                        'name' => '*.php',
                    ]
                )
            );

        $configFile = $this->buildYml(
            [
                'finder' => [
                    'in' => 'src',

                ],
                'license' => 'my_license',
            ]
        );

        self::assertEquals($config, ConfigFactory::createFromConfigFile($configFile)[0]);
    }

    public function testCreateFromConfigFileOverrideConfigSource()
    {
        copy(implode(DIRECTORY_SEPARATOR, [$this->fixturesDir, '.licenser.yml']), $this->tempDir.DIRECTORY_SEPARATOR.'.licenser.yml');
        $fileSystem = new Filesystem();
        $fileSystem->mkdir($this->tempDir.DIRECTORY_SEPARATOR.'src');
        $fileSystem->mkdir($this->tempDir.DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR.'other');
        $fileSystem->touch($this->tempDir.DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR.'other'.DIRECTORY_SEPARATOR.'file.php');

        $config = Config::create()
            ->setLicense(file_get_contents(implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'src', 'licenses', 'default'])))
            ->setFinderBuilder(
                FinderBuilder::create(
                    [
                        'in' => [realpath($this->tempDir).DIRECTORY_SEPARATOR.'src'],
                        'name' => ['file.php'],
                        'path' => ['other'],
                    ]
                )
            );

        $input = $this->buildInput(
            [
                'source' => realpath($this->tempDir.DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR.'other'.DIRECTORY_SEPARATOR.'file.php'),
            ]
        );

        $file = new \SplFileInfo($this->tempDir.DIRECTORY_SEPARATOR.'.licenser.yml');

        self::assertEquals($config, ConfigFactory::createFromConfigFile($file, $input)[0]);
    }

    public function testCreateFromConfigFileOverrideConfigSourceDirectory()
    {
        copy(implode(DIRECTORY_SEPARATOR, [$this->fixturesDir, '.licenser.yml']), $this->tempDir.DIRECTORY_SEPARATOR.'.licenser.yml');
        $fileSystem = new Filesystem();
        $fileSystem->mkdir($this->tempDir.DIRECTORY_SEPARATOR.'src');
        $fileSystem->mkdir($this->tempDir.DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR.'other');

        $config = Config::create()
            ->setLicense(file_get_contents(implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'src', 'licenses', 'default'])))
            ->setFinderBuilder(
                FinderBuilder::create(
                    [
                        'in' => [realpath($this->tempDir).DIRECTORY_SEPARATOR.'src'],
                        'name' => '*.php',
                        'path' => ['other'],
                    ]
                )
            );

        $input = $this->buildInput(
            [
                'source' => $this->tempDir.DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR.'other',
            ]
        );

        $file = new \SplFileInfo($this->tempDir.DIRECTORY_SEPARATOR.'.licenser.yml');
        self::assertEquals($config, ConfigFactory::createFromConfigFile($file, $input)[0]);
    }

    public function testCreateFromConfigFileOverrideConfigSourceSameConfigFile()
    {
        copy(implode(DIRECTORY_SEPARATOR, [$this->fixturesDir, '.licenser.yml']), $this->tempDir.DIRECTORY_SEPARATOR.'.licenser.yml');
        $fileSystem = new Filesystem();
        $fileSystem->mkdir($this->tempDir.DIRECTORY_SEPARATOR.'src');
        $fileSystem->mkdir($this->tempDir.DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR.'other');

        $config = Config::create()
            ->setLicense(file_get_contents(implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'src', 'licenses', 'default'])))
            ->setFinderBuilder(
                FinderBuilder::create(
                    [
                        'in' => [realpath($this->tempDir).DIRECTORY_SEPARATOR.'src'],
                        'name' => '*.php',
                    ]
                )
            );

        $input = $this->buildInput(
            [
                'source' => $this->tempDir.DIRECTORY_SEPARATOR.'.licenser.yml',
                '--config' => $this->tempDir.DIRECTORY_SEPARATOR.'.licenser.yml',
            ]
        );

        $file = new \SplFileInfo($this->tempDir.DIRECTORY_SEPARATOR.'.licenser.yml');
        self::assertEquals($config, ConfigFactory::createFromConfigFile($file, $input)[0]);
    }

    public function testCreateFromConfigFileMultiFinder()
    {
        $config = Config::create()
            ->setLicense(file_get_contents(implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'src', 'licenses', 'default'])))
            ->setFinderBuilder(
                FinderBuilder::create(
                    [
                        'in' => [realpath($this->tempDir)],
                        'name' => '*.php',
                    ]
                )
            );

        $configJs = Config::create()
            ->setLicense(file_get_contents(implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'src', 'licenses', 'default'])))
            ->setFinderBuilder(
                FinderBuilder::create(
                    [
                        'in' => [realpath(sys_get_temp_dir().DIRECTORY_SEPARATOR.'src')],
                        'name' => '*.js',
                    ]
                )
            );

        $configFile = $this->buildYml(
            [
                'finders' => [
                    'php' => [
                        'in' => 'licenser',
                    ],
                    'javascript' => [
                        'in' => 'src',
                        'name' => '*.js',
                    ],
                ],
            ]
        );

        self::assertEquals([$config, $configJs], ConfigFactory::createFromConfigFile($configFile));
    }

    /**
     * buildInput.
     *
     * @param $array
     *
     * @return InputInterface
     */
    protected function buildInput($array)
    {
        $input = new ArrayInput($array);
        $input->bind((new LicenserCommand())->getDefinition());

        return $input;
    }

    /**
     * buildYml.
     *
     * @param $array
     *
     * @return \SplFileInfo
     */
    protected function buildYml($array)
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'licenser');
        file_put_contents($tempFile, Yaml::dump($array));

        return new \SplFileInfo($tempFile);
    }
}
