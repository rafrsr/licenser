<?php

/*
 * LICENSE: This file is subject to the terms and conditions defined in
 * file 'LICENSE', which is part of this source code package.
 *
 * @copyright 2016 Copyright(c) - All rights reserved.
 *
 * @author Rafael SR <https://github.com/rafrsr>
 * @package Licenser
 * @version 1.0.1
 */

namespace Rafrsr\Licenser\Command;

use Rafrsr\Licenser\Config;
use Rafrsr\Licenser\Licenser;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

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

        if ($configFile = $input->getOption('config')) {
            $config = Config::createFromYml($configFile);

            //allow source override by command
            //https://github.com/rafrsr/licenser/issues/1
            if (($source = $input->getArgument('source'))
                && file_exists($source)
                && realpath($source) !== realpath($configFile)//ignore run the command in the config file
            ) {
                //TODO: find a best way to do this without reflection
                $finderReflection = new \ReflectionClass(Finder::class);
                $dirsProperty = $finderReflection->getProperty('dirs');
                $dirsProperty->setAccessible(true);
                if (is_dir($source)) {
                    $dirsProperty->setValue($config->getFinder(), [realpath($source)]);
                } else {
                    $file = new \SplFileInfo($source);
                    $dirsProperty->setValue($config->getFinder(), [realpath($file->getPath())]);
                    $nameProperty = $finderReflection->getProperty('names');
                    $nameProperty->setAccessible(true);
                    $nameProperty->setValue($config->getFinder(), [$file->getFilename()]);
                }
            }

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
