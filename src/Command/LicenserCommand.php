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

namespace Rafrsr\Licenser\Command;

use Rafrsr\Licenser\Config;
use Rafrsr\Licenser\Licenser;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Yaml\Yaml;

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

        if ($configFile = $input->getOption('config')) {
            $configArray = $this->parseYml($configFile);

            //allow source override by command
            //https://github.com/rafrsr/licenser/issues/1
            if (($source = $input->getArgument('source'))
                && file_exists($source)
                && realpath($source) !== realpath($configFile)//ignore run the command in the config file
            ) {
                $source = realpath($source);
                $ins = isset($configArray['finder']['in']) ? $configArray['finder']['in'] : [];
                //obtaining related path based on "in" directories
                //allowing filter results using the finder config
                foreach ($ins as $in) {
                    if (strpos($source, $in) !== false) {
                        $path = str_replace($in, null, $source);
                        //remove any \ or / in the path start
                        $path = preg_replace('/^[\/\\\]/', null, $path);
                        $path = str_replace('\\', '/', $path); //convert \ -> /
                        if (is_file($source)) {
                            $path = pathinfo($path, PATHINFO_DIRNAME);
                        }
                        $configArray['finder']['path'] = $path;
                    }
                }

                if (is_file($source)) {
                    $configArray['finder']['name'] = pathinfo($source, PATHINFO_BASENAME);
                }
            }
        } else {
            $configArray = $this->parseInput($input);
        }

        $this->buildLicenser(Config::createFromArray($configArray), $output)->process($mode);

        return 0;
    }

    /**
     * Parse input and return config array.
     *
     * @param InputInterface $input
     *
     * @return array array with config, ready to create a config instance
     */
    protected function parseInput(InputInterface $input)
    {
        $source = $input->getArgument('source');
        $license = $input->getArgument('license');

        $configArray = [];
        if (file_exists($source)) {
            if (is_dir($source)) {
                $configArray['finder']['name'] = '*.php';
                $configArray['finder']['in'] = realpath($source);
            } else {
                $file = new \SplFileInfo($source);
                $configArray['finder']['name'] = $file->getFilename();
                $configArray['finder']['in'] = $file->getPath();
                $configArray['finder']['depth'] = 0;
            }
        } else {
            throw new FileNotFoundException(null, 0, null, $source);
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

        $configArray['parameters'] = $params;
        $configArray['license'] = $license;

        return $configArray;
    }

    /**
     * Create config from yml input.
     *
     * @param string $ymlFile
     *
     * @return array
     */
    protected function parseYml($ymlFile)
    {
        $file = new \SplFileInfo(realpath($ymlFile));
        $workingDir = $file->getPath();

        $yamlConfig = file_get_contents($file->getPathname());
        $configArray = Yaml::parse($yamlConfig);

        if (!isset($configArray['license_content'])) {
            $license = isset($configArray['license']) ? $configArray['license'] : 'default';
            //resolve absolute path
            if (file_exists($workingDir.DIRECTORY_SEPARATOR.$license)) {
                $license = $workingDir.DIRECTORY_SEPARATOR.$license;
            }
            $configArray['license'] = $license;
        }

        if (isset($configArray['finder']['in'])) {
            $inArray = [];
            foreach ((array) $configArray['finder']['in'] as $in) {
                $inArray[] = $workingDir.DIRECTORY_SEPARATOR.$in;
            }
            $configArray['finder']['in'] = $inArray;
        } else {
            throw new \LogicException('Invalid configuration, value of "finder.in" is required to locate source files.');
        }

        if (!isset($configArray['finder']['name'])) {
            $configArray['finder']['name'] = '*.php';
        }

        return $configArray;
    }

    /**
     * buildLicenser.
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
