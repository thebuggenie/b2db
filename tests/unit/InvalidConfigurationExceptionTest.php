<?php

use PHPUnit\Framework\TestCase;

class InvalidConfigurationExceptionTest extends TestCase
{
    public function test_message_is_set()
    {
        $message = "Test message";

        $exception = new b2db\InvalidConfigurationException($message);

        $this->assertEquals($message, $exception->getMessage());
    }
}
