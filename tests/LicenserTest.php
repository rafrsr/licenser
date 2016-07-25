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

namespace Rafrsr\Licenser\Tests;

use Rafrsr\Licenser\Config;
use Rafrsr\Licenser\Licenser;
use Rafrsr\Licenser\ProcessLogger;
use Symfony\Component\Console\Tests\Fixtures\DummyOutput;

/**
 * Class LicenserTest.
 */
class LicenserTest extends \PHPUnit_Framework_TestCase
{
    use LicenseTesterSetUpTrait;

    public function testBasicLicenser()
    {
        Licenser::create($this->config, $this->logger)->process(Licenser::MODE_NORMAL);
        $expected
            = <<<EOS
/*
 * LICENSE: This file is subject to the terms and conditions defined in
 * file 'LICENSE', which is part of this source code package.
 *
 * @copyright 2016 Copyright(c) - All rights reserved.
 */
EOS;
        self::assertContains($expected, file_get_contents($this->tempDir.DIRECTORY_SEPARATOR.'file.php'));
        self::assertContains($expected, file_get_contents($this->tempDir.DIRECTORY_SEPARATOR.'file2.php'));
        self::assertNotContains(' * Old license File', file_get_contents($this->tempDir.DIRECTORY_SEPARATOR.'file2.php'));
        self::assertContains($expected, file_get_contents($this->tempDir.DIRECTORY_SEPARATOR.'file3.php'));
        self::assertNotContains($expected, file_get_contents($this->tempDir.DIRECTORY_SEPARATOR.'license'));

        $this->logger->finishProcess();
        self::assertContains('4 file(s) has been processed in', $this->output->fetch());
    }

    public function testLicenserCheckIntegrity()
    {
        $this->config->setParameters(['package' => 'Licenser'])
            ->setLicense(file_get_contents($this->fixturesDir.DIRECTORY_SEPARATOR.'license'));

        $this->config->getFinder()->name('*.js');
        Licenser::create($this->config, $this->logger)->process(Licenser::MODE_NORMAL);

        $expected = $this->fixturesDir.DIRECTORY_SEPARATOR.'expected'.DIRECTORY_SEPARATOR;
        self::assertFileEquals($expected.'file.php', $this->tempDir.DIRECTORY_SEPARATOR.'file.php');
        self::assertFileEquals($expected.'file2.php', $this->tempDir.DIRECTORY_SEPARATOR.'file2.php');
        self::assertFileEquals($expected.'file3.php', $this->tempDir.DIRECTORY_SEPARATOR.'file3.php');
        self::assertFileEquals($expected.'shortag.php', $this->tempDir.DIRECTORY_SEPARATOR.'shortag.php');
        self::assertFileEquals($expected.'javascript.js', $this->tempDir.DIRECTORY_SEPARATOR.'javascript.js');

        $this->logger->finishProcess();
        self::assertContains('5 file(s) has been processed in', $this->output->fetch());
    }

    public function testLicenserWithParameters()
    {
        $this->config->setParameters(['author' => 'Rafael SR <https://github.com/rafrsr>']);
        Licenser::create($this->config, $this->logger)->process(Licenser::MODE_NORMAL);
        $expected
            = <<<EOS
/*
 * LICENSE: This file is subject to the terms and conditions defined in
 * file 'LICENSE', which is part of this source code package.
 *
 * @copyright 2016 Copyright(c) - All rights reserved.
 *
 * @author Rafael SR <https://github.com/rafrsr>
 */
EOS;
        self::assertContains($expected, file_get_contents($this->tempDir.DIRECTORY_SEPARATOR.'file.php'));
        self::assertContains($expected, file_get_contents($this->tempDir.DIRECTORY_SEPARATOR.'file2.php'));
        self::assertContains($expected, file_get_contents($this->tempDir.DIRECTORY_SEPARATOR.'file3.php'));
        self::assertNotContains($expected, file_get_contents($this->tempDir.DIRECTORY_SEPARATOR.'license'));

        $this->logger->finishProcess();
        self::assertContains('4 file(s) has been processed in', $this->output->fetch());
    }

    public function testLicenserDryRun()
    {
        Licenser::create($this->config, $this->logger)->process(Licenser::MODE_DRY_RUN);
        $expected
            = <<<EOS
/*
 * LICENSE: This file is subject to the terms and conditions defined in
 * file 'LICENSE', which is part of this source code package.
 *
 * @copyright 2016 Copyright(c) - All rights reserved.
 */
EOS;
        self::assertNotContains($expected, file_get_contents($this->tempDir.DIRECTORY_SEPARATOR.'file2.php'));
        self::assertNotContains($expected, file_get_contents($this->tempDir.DIRECTORY_SEPARATOR.'file3.php'));
        self::assertNotContains($expected, file_get_contents($this->tempDir.DIRECTORY_SEPARATOR.'file3.php'));

        $this->logger->finishProcess();
        self::assertContains('4 file(s) has been processed in', $this->output->fetch());
    }

    public function testLicenserCheckOnly()
    {
        $this->config->getFinder()->name('*.js');
        Licenser::create($this->config, $this->logger)->process(Licenser::MODE_CHECK_ONLY);
        $this->logger->startProcess(Licenser::MODE_CHECK_ONLY);
        $expected
            = <<<EOS
/*
 * LICENSE: This file is subject to the terms and conditions defined in
 * file 'LICENSE', which is part of this source code package.
 *
 * @copyright 2016 Copyright(c) - All rights reserved.
 */
EOS;
        self::assertNotContains($expected, file_get_contents($this->tempDir.DIRECTORY_SEPARATOR.'file2.php'));
        self::assertNotContains($expected, file_get_contents($this->tempDir.DIRECTORY_SEPARATOR.'file3.php'));
        self::assertNotContains($expected, file_get_contents($this->tempDir.DIRECTORY_SEPARATOR.'file3.php'));
        self::assertNotContains($expected, file_get_contents($this->tempDir.DIRECTORY_SEPARATOR.'javascript.js'));

        $this->logger->finishProcess();
        $output = $this->output->fetch();
        self::assertContains('[WARN] 5 file(s) should be updated.', $output);
        self::assertContains('5 file(s) has been processed in', $output);

        Licenser::create($this->config, $this->logger)->process(Licenser::MODE_NORMAL);

        //recheck after process
        $this->output = new DummyOutput();
        $this->logger = new ProcessLogger($this->output);
        $this->logger->startProcess(Licenser::MODE_CHECK_ONLY);
        Licenser::create($this->config, $this->logger)->process(Licenser::MODE_CHECK_ONLY);
        self::assertContains($expected, file_get_contents($this->tempDir.DIRECTORY_SEPARATOR.'file2.php'));
        self::assertContains($expected, file_get_contents($this->tempDir.DIRECTORY_SEPARATOR.'file3.php'));
        self::assertContains($expected, file_get_contents($this->tempDir.DIRECTORY_SEPARATOR.'file3.php'));
        self::assertContains($expected, file_get_contents($this->tempDir.DIRECTORY_SEPARATOR.'javascript.js'));

        $this->logger->finishProcess();
        $output = $this->output->fetch();
        self::assertContains('[OK] All files contains a valid license header. ', $output);
        self::assertContains('5 file(s) has been processed in', $output);
    }

    public function testSetGetConfig()
    {
        $licenser = Licenser::create($this->config);
        self::assertEquals($licenser->getConfig(), $this->config);
        $newConfig = Config::create();
        $licenser->setConfig($newConfig);
        self::assertEquals($licenser->getConfig(), $newConfig);
    }

    public function testSetGetLogger()
    {
        $licenser = Licenser::create($this->config, $this->logger);
        self::assertEquals($licenser->getLogger(), $this->logger);
        $newLogger = new ProcessLogger(new DummyOutput());
        $licenser->setLogger($newLogger);
        self::assertEquals($licenser->getLogger(), $newLogger);
    }
}
