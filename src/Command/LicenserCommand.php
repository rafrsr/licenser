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

namespace Rafrsr\Licenser\Command;

use Rafrsr\Licenser\Config;
use Rafrsr\Licenser\ConfigFactory;
use Rafrsr\Licenser\Licenser;
use Rafrsr\Licenser\ProcessLogger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * LicenserCommand.
 *
 * @author RafaelSR <https://github.com/rafrsr>
 */
class LicenserCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('licenser')
            ->setDescription('Runs the licenser against the given source path')
            ->addArgument(
                'source',
                InputArgument::OPTIONAL,
                'The path to the source files that the licenser will process'
            )
            ->addArgument(
                'license',
                InputArgument::OPTIONAL,
                'The name of a built in license or a path to the file containing your custom license header doc block as it will appear when prepended to your source files',
                'default'
            )
            ->addOption(
                'config',
                'c',
                InputOption::VALUE_OPTIONAL,
                '.yml file with configuration'
            )
            ->addOption(
                'param',
                'p',
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Parameters to pass to the license file'
            )
            ->addOption(
                'finder',
                'f',
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Configure the finder'
            )
            ->addOption(
                'check-only',
                '',
                InputOption::VALUE_NONE,
                'Return success if all files are ok, otherwise return error'
            )
            ->addOption(
                'dry-run',
                '',
                InputOption::VALUE_NONE,
                'If specified, the command will report a list of affected files but will make no modifications'
            );
    }

    /**
     * Executes the command.
     *
     * @param InputInterface  $input  An input stream
     * @param OutputInterface $output An output stream
     *
     * @return int null or 0 if everything went fine, or an error code
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dryRun = $input->getOption('dry-run');
        $checkOnly = $input->getOption('check-only');

        $mode = Licenser::MODE_NORMAL;
        if ($checkOnly) {
            $mode = Licenser::MODE_CHECK_ONLY;
        } elseif ($dryRun) {
            $mode = Licenser::MODE_DRY_RUN;
        }

        $logger = new ProcessLogger($output);
        $logger->startProcess($mode);

        if ($configFile = $this->extractConfigFile($input)) {
            foreach (ConfigFactory::createFromConfigFile($configFile, $input) as $config) {
                $this->buildLicenser($config, $logger)->process($mode);
            }
        } else {
            $config = ConfigFactory::createFromCommandLine($input);
            $this->buildLicenser($config, $logger)->process($mode);
        }

        $logger->finishProcess();

        if ($mode === Licenser::MODE_CHECK_ONLY && $logger->countAdditions() + $logger->countUpdates() > 0) {
            return 1;
        }

        return 0;
    }

    /**
     * buildLicenser.
     *
     * @param Config        $config
     * @param ProcessLogger $logger
     *
     * @return Licenser
     */
    protected function buildLicenser(Config $config, ProcessLogger $logger)
    {
        return Licenser::create($config, $logger);
    }

    /**
     * @param InputInterface $input
     *
     * @return null|\SplFileInfo
     */
    private function extractConfigFile(InputInterface $input)
    {
        $yml = $input->getOption('config');
        if ($yml) {
            return new \SplFileInfo(realpath($yml));
        }

        return;
    }
}
