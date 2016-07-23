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

namespace Rafrsr\Licenser\tests;

use Rafrsr\Licenser\Config;
use Symfony\Component\Console\Tests\Fixtures\DummyOutput;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

trait LicenseTesterSetUpTrait
{
    /**
     * @var string
     */
    protected $tempDir;

    /**
     * @var string
     */
    protected $fixturesDir;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var DummyOutput
     */
    protected $output;

    public function setUp()
    {
        $fileSystem = new Filesystem();

        $temp = sys_get_temp_dir();
        $this->tempDir = $temp.DIRECTORY_SEPARATOR.'licenser';
        $fileSystem->remove($this->tempDir);
        $fileSystem->mkdir($this->tempDir);
        $this->fixturesDir = __DIR__.DIRECTORY_SEPARATOR.'Fixtures';
        $fileSystem->mirror($this->fixturesDir.DIRECTORY_SEPARATOR.'origin', $this->tempDir, null, ['override' => true]);
        $fileSystem->copy($this->fixturesDir.DIRECTORY_SEPARATOR.'license', $this->tempDir.DIRECTORY_SEPARATOR.'license', true);

        $finder = Finder::create()->in($this->tempDir)->name('*.php');
        $this->config = (Config::create()->setFinder($finder));
        $this->config->setLicense(file_get_contents(implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'src', 'licenses', 'default'])));
        $this->output = new DummyOutput();
    }
}
