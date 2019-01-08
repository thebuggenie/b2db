<?php

use PHPUnit\Framework\TestCase;

use b2db\Cache;
use b2db\InvalidConfigurationException;

class CacheTest extends TestCase
{

    public function test_can_set_type_through_constructor()
    {
        $cache = new Cache(100);

        $this->assertEquals(100, $cache->getType());
    }

    public function test_can_set_option_enabled_through_constructor()
    {
        $cache = new Cache(0, ['enabled' => false]);

        $this->assertEquals(false, $cache->isEnabled());
    }

    public function test_is_enabled_by_default()
    {
        $cache = new Cache(0);

        $this->assertEquals(true, $cache->isEnabled());
    }

    public function test_can_set_option_path_through_constructor()
    {
        $cache = new Cache(0, ['path' => '/tmp/b2db-test-cache']);

        $this->assertInstanceOf(Cache::class, $cache);
    }

    public function test_throws_exception_in_constructor_if_option_path_set_to_non_writeable_directory()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageRegExp('/.*\/tmp\/b2db-test-non-existing-directory.*/');

        $cache = new Cache(0, ['path' => '/tmp/b2db-test-non-existing-directory']);
    }

    public function test_throws_exception_in_constructor_if_unsupported_option_is_set()
    {
        $this->expectException(InvalidConfigurationException::class);

        $cache = new Cache(0, ['bogusoption' => true]);

    }
}
