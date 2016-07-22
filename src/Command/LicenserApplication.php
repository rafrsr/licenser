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

use Rafrsr\Licenser\Licenser;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;

/**
 * LicenserApplication
 *
 * @author RafaelSR <https://github.com/rafrsr>
 *
 */
class LicenserApplication extends Application
{
    /**
     * LicenserApplication constructor.
     */
    public function __construct()
    {
        parent::__construct('Licenser', Licenser::VERSION);
    }

    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        $inputDefinition = parent::getDefinition();
        // clear out the normal first argument, which is the command name
        $inputDefinition->setArguments();

        return $inputDefinition;
    }

    /**
     * {@inheritdoc}
     */
    protected function getCommandName(InputInterface $input)
    {
        return 'licenser';
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultCommands()
    {
        // Keep the core default commands to have the HelpCommand
        // which is used when using the --help option
        $defaultCommands = parent::getDefaultCommands();

        $defaultCommands[] = new LicenserCommand();

        return $defaultCommands;
    }
}
