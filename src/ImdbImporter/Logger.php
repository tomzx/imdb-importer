<?php

namespace ImdbImporter;

use Psr\Log\AbstractLogger;

/**
 * Simple logger that just echos its content
 */
class Logger extends AbstractLogger
{
    /**
     * Logs with an arbitrary level.
     *
     * @param mixed $level
     * @param string $message
     * @param array $context
     * @return null
     */
    public function log($level, $message, array $context = array())
    {
        echo $message . PHP_EOL;
    }
}
