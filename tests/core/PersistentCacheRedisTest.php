<?php

use PHPUnit\Framework\TestCase;

class PersistentCacheRedisTest extends TestCase
{
	private PersistentCacheRedis $_cache;

	protected function setUp(): void
	{
		putenv('CACHE_REDIS_HOST=redis-test');
		putenv('CACHE_REDIS_PORT=6379');
		putenv('CACHE_REDIS_TIMEOUT=2');

		$this->_cache = new PersistentCacheRedis();

		try {
			$this->_cache->assertAvailable();
		} catch (RuntimeException $e) {
			$this->markTestSkipped('redis-test not available (' . $e->getMessage() . ')');
		}
	}

	public function testSetAndGetValueUsingEnvDrivenRedisHost(): void
	{
		$key = 'cache_test_' . uniqid();

		$this->_cache->set($key, 'value');

		$this->assertSame('value', $this->_cache->get($key));
		$this->assertTrue($this->_cache->exists($key));
	}
}
