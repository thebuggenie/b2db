<?php

use PHPUnit\Framework\TestCase;

class ExceptionTest extends TestCase
{
    public function test_message_is_set()
    {
        $message = "Test message";

        $exception = new b2db\Exception($message);

        $this->assertEquals($message, $exception->getMessage());
    }

    public function test_sql_is_set_when_passed_in()
    {
        $message = "Test message";
        $sqlQuery = "INSERT INTO mytable VALUES(1)";

        $exception = new b2db\Exception($message, $sqlQuery);

        $this->assertEquals($sqlQuery, $exception->getSQL());
    }

    public function test_sql_is_set_to_null_when_not_passed_in()
    {
        $message = "Test message";

        $exception = new b2db\Exception($message);

        $this->assertEquals(null, $exception->getSQL());
    }
}
