<?php

/*
 * LICENSE: This file is subject to the terms and conditions defined in
 * file 'LICENSE', which is part of this source code package.
 * 
 * @copyright 2016 Copyright(c) - All rights reserved.
 * 
 * @author Rafael SR <https://github.com/rafrsr>
 * @package Licenser
 * @version 1.0.0-alpha
 */

namespace Rafrsr\Licenser;

use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * Licenser
 *
 * @author RafaelSR <https://github.com/rafrsr>
 */
class Licenser
{
    const VERSION = '1.0.0-alpha';

    const MODE_NORMAL = 1;
    const MODE_DRY_RUN = 2;
    const MODE_CHECK_ONLY = 4;

    /**
     * @var Config
     */
    private $config;

    /**
     * An output stream
     *
     * @var OutputInterface
     */
    private $output;

    /**
     * Licenser constructor.
     *
     * @param Config          $config
     * @param OutputInterface $output
     */
    public function __construct(Config $config, OutputInterface $output)
    {
        $this->output = $output;
        $this->config = $config;
    }

    /**
     * create
     *
     * @param Config          $config
     * @param OutputInterface $output
     *
     * @return Licenser
     */
    public static function create(Config $config, OutputInterface $output)
    {
        return new static($config, $output);
    }

    /**
     * @return Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param Config $config
     *
     * @return $this
     */
    public function setConfig(Config $config)
    {
        $this->config = $config;

        return $this;
    }

    /**
     * @return OutputInterface
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * @param OutputInterface $output
     *
     * @return $this
     */
    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;

        return $this;
    }

    /**
     * Process current config
     *
     * @param int $mode mode to run the process
     *
     * @return int null or 0 if everything went fine, or an error code
     */
    public function process($mode = self::MODE_NORMAL)
    {
        $license = $this->parseLicense($this->config->getLicense());

        $total = 0;
        $additions = 0;
        $updates = 0;
        $untouched = 0;
        $ignored = 0;

        $watch = new Stopwatch();
        $watch->start('licenser');

        foreach ($this->config->getFinder()->files() as $file) {
            $content = $file->getContents();
            $total++;

            //must start with <?php
            if (preg_match('/^<\?php/', $content)) {
                $licensedContent = null;
                //match license in the file header
                if (!preg_match('/^<\?php\s+\/\**(\s?\**[\S ]+\n*)((?m)^\s*\*[\S ]*\n)+/', $content, $matches)) {
                    $this->output->writeln(sprintf('<fg=green>[+] %s</>', $file->getRealPath()), OutputInterface::VERBOSITY_VERY_VERBOSE);
                    $licensedContent = preg_replace('/^<\?php\s+/', "<?php\n\n".$license."\n", $content);
                    $additions++;
                } elseif (array_key_exists(0, $matches)) {
                    $phpHeader = "<?php\n\n".$license;
                    if ($matches[0] !== $phpHeader) {
                        $this->output->writeln(sprintf('<fg=cyan>[u] %s</>', $file->getRealPath()), OutputInterface::VERBOSITY_VERY_VERBOSE);
                        $licensedContent = str_replace($matches[0], $phpHeader, $content);
                        $updates++;
                    } else {
                        $this->output->writeln(sprintf('<fg=yellow>[=] %s</>', $file->getRealPath()), OutputInterface::VERBOSITY_VERY_VERBOSE);
                        $untouched++;
                    }
                }

                if ($licensedContent !== null && $mode === self::MODE_NORMAL) {
                    file_put_contents($file->getPathname(), $licensedContent);
                }
            } else {
                $this->output->writeln(sprintf('<fg=red>[-] %s</>', $file->getRealPath()), OutputInterface::VERBOSITY_VERY_VERBOSE);
                $ignored++;
            }
        }
        $event = $watch->stop('licenser');

        $formatter = new FormatterHelper();

        //summary
        $this->output->writeln('', OutputInterface::VERBOSITY_VERBOSE);
        $this->output->writeln(sprintf('<fg=green>[+] Additions: %s</>', $additions), OutputInterface::VERBOSITY_VERBOSE);
        $this->output->writeln(sprintf('<fg=cyan>[u] Updates: %s</>', $updates), OutputInterface::VERBOSITY_VERBOSE);
        $this->output->writeln(sprintf('<fg=yellow>[=] Untouched: %s</>', $untouched), OutputInterface::VERBOSITY_VERBOSE);
        $this->output->writeln(sprintf('<fg=red>[-] Ignored: %s</>', $ignored), OutputInterface::VERBOSITY_VERBOSE);
        $this->output->writeln('');

        $processMessage = sprintf('%s file(s) has been processed in %s ms, memory usage %.2F MiB', $total, $event->getDuration(), $event->getMemory() / 1024 / 1024);
        if ($mode & self::MODE_NORMAL || $mode & self::MODE_DRY_RUN) {
            $style = new OutputFormatterStyle('white', ($mode === self::MODE_DRY_RUN) ? 'cyan' : 'green', ['bold']);
            $this->output->getFormatter()->setStyle('success', $style);
            $formattedBlock = $formatter->formatBlock($processMessage, 'success', true);
            $this->output->writeln($formattedBlock);
        } elseif ($mode & self::MODE_CHECK_ONLY) {
            $needUpdate = (($additions + $updates) > 0);
            if ($needUpdate) {
                $successMsg = sprintf('[ERROR] %s file(s) should be updated.', $additions + $updates);
            } else {
                $successMsg = '[OK] All files contains a valid license header.';
            }
            $style = new OutputFormatterStyle('white', $needUpdate ? 'red' : 'green', ['bold']);
            $this->output->getFormatter()->setStyle('success', $style);
            $formattedBlock = $formatter->formatBlock([$successMsg, $processMessage], 'success', true);
            $this->output->writeln($formattedBlock);

            if ($needUpdate) {
                return 1;
            } else {
                return 0;
            }
        }

        if ($mode === self::MODE_DRY_RUN) {
            $this->output->writeln('');
            $this->output->writeln('<fg=yellow>NOTE: The command run in dry-run mode, it not made any changes.</>');
        }

        return 0;
    }

    /**
     * Process given license returning comment format
     *
     * @param string $rawLicense raw license to process
     *
     * @return string parsed license
     * @throws \Exception
     * @throws \Throwable
     */
    private function parseLicense($rawLicense)
    {
        $twig = new \Twig_Environment(new \Twig_Loader_Filesystem());

        $license = $twig->createTemplate($rawLicense)->render($this->config->getParameters());
        $license = trim($license);

        //create license comment
        $license = preg_replace("/\n/", "\n * ", $license);
        $license = "/*\n * $license\n */\n";

        return $license;
    }
}
