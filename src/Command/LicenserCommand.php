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

namespace Rafrsr\Licenser\Command;

use Rafrsr\Licenser\Config;
use Rafrsr\Licenser\Licenser;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * LicenserCommand
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
     * Executes the command
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

        if ($input->getOption('config')) {
            $config = Config::createFromYml($input->getOption('config'));
        } else {
            $config = Config::createFromInput($input);
        }

        $this->buildLicenser($config, $output)->process($mode);

        return 0;
    }

    /**
     * buildLicenser
     *
     * @param Config          $config
     * @param OutputInterface $output
     *
     * @return Licenser
     */
    protected function buildLicenser(Config $config, OutputInterface $output)
    {
        return Licenser::create($config, $output);
    }
}
