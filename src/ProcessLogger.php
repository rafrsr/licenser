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

namespace Rafrsr\Licenser;

use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * ProcessLogger.
 */
class ProcessLogger
{
    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var int
     */
    private $mode;

    /**
     * @var Stopwatch
     */
    private $watch;

    /**
     * @var array
     */
    private $processed = [];

    /**
     * @var array
     */
    private $additions = [];

    /**
     * @var array
     */
    private $updates = [];

    /**
     * @var array
     */
    private $untouched = [];

    /**
     * ProcessLogger constructor.
     *
     * @param OutputInterface $output
     */
    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
        $this->watch = new Stopwatch();
    }

    /**
     * startProcess.
     *
     * @param int $mode
     */
    public function startProcess($mode = Licenser::MODE_NORMAL)
    {
        $this->mode = $mode;
        $this->watch->start('licenser');
    }

    /**
     * @param SplFileInfo $file
     */
    public function addition(SplFileInfo $file)
    {
        $this->additions[] = $file;
        $this->processed[] = $file;
        $this->output->writeln(sprintf('<fg=green>[+] %s</>', $file->getRealPath()), OutputInterface::VERBOSITY_VERY_VERBOSE);
    }

    /**
     * @param SplFileInfo $file
     */
    public function updated(SplFileInfo $file)
    {
        $this->updates[] = $file;
        $this->processed[] = $file;
        $this->output->writeln(sprintf('<fg=cyan>[u] %s</>', $file->getRealPath()), OutputInterface::VERBOSITY_VERY_VERBOSE);
    }

    /**
     * @param SplFileInfo $file
     */
    public function untouched(SplFileInfo $file)
    {
        $this->untouched[] = $file;
        $this->processed[] = $file;
        $this->output->writeln(sprintf('<fg=yellow>[=] %s</>', $file->getRealPath()), OutputInterface::VERBOSITY_VERY_VERBOSE);
    }

    /**
     * @return int
     */
    public function countAdditions()
    {
        return count($this->additions);
    }

    /**
     * @return int
     */
    public function countTotal()
    {
        return count($this->processed);
    }

    /**
     * @return int
     */
    public function countUpdates()
    {
        return count($this->updates);
    }

    /**
     * @return int
     */
    public function countUntouched()
    {
        return count($this->untouched);
    }

    /**
     * Finish current licenser process log and display results in the output.
     */
    public function finishProcess()
    {
        $total = $this->countTotal();
        $additions = $this->countAdditions();
        $updates = $this->countUpdates();
        $untouched = $this->countUntouched();

        $formatter = new FormatterHelper();

        //summary
        $this->output->writeln('', OutputInterface::VERBOSITY_VERBOSE);
        $this->output->writeln(sprintf('<fg=green>[+] Additions: %s</>', $additions), OutputInterface::VERBOSITY_VERBOSE);
        $this->output->writeln(sprintf('<fg=cyan>[u] Updates: %s</>', $updates), OutputInterface::VERBOSITY_VERBOSE);
        $this->output->writeln(sprintf('<fg=yellow>[=] Untouched: %s</>', $untouched), OutputInterface::VERBOSITY_VERBOSE);
        $this->output->writeln('');

        if ($this->watch->isStarted('licenser')) {
            $event = $this->watch->stop('licenser');

            $processMessage = sprintf('%s file(s) has been processed in %s ms, memory usage %.2F MiB', $total, $event->getDuration(), $event->getMemory() / 1024 / 1024);
            if ($this->mode & Licenser::MODE_NORMAL || $this->mode & Licenser::MODE_DRY_RUN) {
                $style = new OutputFormatterStyle('white', ($this->mode === Licenser::MODE_DRY_RUN) ? 'cyan' : 'green', ['bold']);
                $this->output->getFormatter()->setStyle('success', $style);
                $formattedBlock = $formatter->formatBlock($processMessage, 'success', true);
                $this->output->writeln($formattedBlock);
            } elseif ($this->mode & Licenser::MODE_CHECK_ONLY) {
                $needUpdate = (($additions + $updates) > 0);
                if ($needUpdate) {
                    $successMsg = sprintf('[WARN] %s file(s) should be updated.', $additions + $updates);
                } else {
                    $successMsg = '[OK] All files contains a valid license header.';
                }
                $style = new OutputFormatterStyle('white', $needUpdate ? 'red' : 'green', ['bold']);
                $this->output->getFormatter()->setStyle('success', $style);
                $formattedBlock = $formatter->formatBlock([$successMsg, $processMessage], 'success', true);
                $this->output->writeln($formattedBlock);
            }

            if ($this->mode === Licenser::MODE_DRY_RUN) {
                $this->output->writeln('');
                $this->output->writeln('<fg=yellow>NOTE: The command run in dry-run mode, it not made any changes.</>');
            }
        }
    }
}
