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

/**
 * Licenser.
 *
 * @author RafaelSR <https://github.com/rafrsr>
 */
class Licenser
{
    const VERSION = '1.0.4';

    const MODE_NORMAL = 1;
    const MODE_DRY_RUN = 2;
    const MODE_CHECK_ONLY = 4;

    /**
     * @var Config
     */
    private $config;

    /**
     * An output stream.
     *
     * @var ProcessLogger
     */
    private $logger;

    /**
     * Licenser constructor.
     *
     * @param Config        $config
     * @param ProcessLogger $logger
     */
    public function __construct(Config $config, ProcessLogger $logger = null)
    {
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * @param Config        $config
     * @param ProcessLogger $logger
     *
     * @return Licenser
     */
    public static function create(Config $config, ProcessLogger $logger = null)
    {
        return new static($config, $logger);
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
     * @return ProcessLogger
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @param ProcessLogger $logger
     *
     * @return $this
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * Process current config.
     *
     * @param int $mode mode to run the process
     */
    public function process($mode = self::MODE_NORMAL)
    {
        $license = $this->parseLicense($this->config->getLicense());

        foreach ($this->config->getFinder()->files() as $file) {
            $content = $file->getContents();
            $licensedContent = null;
            //match license in the file header
            if (preg_match('/^(?\'opentag\'(\S{1,5})?\n+)?(?\'license\'\/\**(\s?\**[\S ]+\n*)((?m)^\s*\*[\S ]*\n)+)/', $content, $matches)) {
                $phpHeader = $matches['opentag'].$license;
                if ($matches[0] !== $phpHeader) {
                    if ($this->logger) {
                        $this->logger->updated($file);
                    }
                    $licensedContent = str_replace($matches[0], $phpHeader, $content);
                } else {
                    if ($this->logger) {
                        $this->logger->untouched($file);
                    }
                }
            } else {
                if ($this->logger) {
                    $this->logger->addition($file);
                }
                $licensedContent = preg_replace('/^(?\'opentag\'(\S{1,5})?\n{1,2})?/', '$1'.$license, $content);
            }

            if ($licensedContent !== null && $mode === self::MODE_NORMAL) {
                file_put_contents($file->getPathname(), $licensedContent);
            }
        }
    }

    /**
     * Process given license returning comment format.
     *
     * @param string $rawLicense raw license to process
     *
     * @throws \Exception
     * @throws \Throwable
     *
     * @return string parsed license
     */
    private function parseLicense($rawLicense)
    {
        $twig = new \Twig_Environment(new \Twig_Loader_Filesystem());

        $license = $twig->createTemplate($rawLicense)->render($this->config->getParameters());
        $license = trim($license);

        //create license comment
        $license = preg_replace("/\n/", "\n * ", $license);
        $license = preg_replace("/ \* \n/", " *\n", $license);//clean empty lines, remove trailing whitespace
        $license = "/*\n * $license\n */\n";

        return $license;
    }
}
