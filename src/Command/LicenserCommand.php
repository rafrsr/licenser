<?php

/**
 * LICENSE: This file is subject to the terms and conditions defined in
 * file 'LICENSE', which is part of this source code package.
 *
 * @copyright 2016 Copyright(c) - All rights reserved.
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
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\File\File;

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
                InputArgument::REQUIRED,
                'The path to the source files that the licenser will process'
            )
            ->addArgument(
                'license',
                InputArgument::OPTIONAL,
                'The name of a built in license or a path to the file containing your custom license header doc block as it will appear when prepended to your source files',
                'default'
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
        $source = $input->getArgument('source');
        $license = $input->getArgument('license');
        $dryRun = $input->getOption('dry-run');
        $checkOnly = $input->getOption('check-only');

        if (file_exists($source)) {
            $finder = Finder::create();
            if (is_dir($source)) {
                $finder->name('*.php')->in(realpath($source));
            } else {
                $file = new File($source);
                $finder
                    ->name($file->getFilename())
                    ->depth(0)
                    ->in($file->getPath());
            }
        } else {
            throw new FileNotFoundException($source);
        }

        $params = [];
        if ($input->getOption('param')) {
            foreach ($input->getOption('param') as $param) {
                if (strpos($param, ':') !== false) {
                    list($name, $value) = explode(':', $param, 2);
                    $params[$name] = $value;
                } else {
                    $msg = sprintf('Invalid parameter "%s", should have the format "name:value", e.g. -p year:%s -p owner:"My Name <email@example.com>"', $param, date('Y'));
                    throw new \InvalidArgumentException($msg);
                }
            }
        }

        $originLicense = $license;
        if (!file_exists($license)) {
            $license = implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'licenses', $license]);
        }

        if (!file_exists($license)) {
            throw new \InvalidArgumentException(sprintf('Invalid license file "%s"', $originLicense));
        }

        $rawLicense = file_get_contents($license);

        $config = Config::create()
            ->setParameters($params)
            ->setLicense($rawLicense)
            ->setFinder($finder);

        $mode = Licenser::MODE_NORMAL;
        if ($checkOnly) {
            $mode = Licenser::MODE_CHECK_ONLY;
        } elseif ($dryRun) {
            $mode = Licenser::MODE_DRY_RUN;
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
