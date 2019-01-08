<?php

namespace b2db;

/**
 * Exception used to indicate invalid configuration parameters.
 */
class InvalidConfigurationException extends \Exception
{
    /**
     * Initialises the exception.
     *
     * @param string $message Message describing the exception thrown.
     */
    public function __construct($message)
    {
        parent::__construct($message);
    }
}