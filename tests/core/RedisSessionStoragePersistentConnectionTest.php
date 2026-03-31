<?php

use PHPUnit\Framework\TestCase;

class RedisSessionStoragePersistentConnectionTest extends TestCase
{
	protected function tearDown(): void
	{
		putenv('RADAPTOR_RUNTIME');
		putenv('SWOOLE_PERSISTENT_REDIS_CONNECTION');
	}

	public function testPersistentRedisConnectionsDefaultToEnabledInSwooleRuntime(): void
	{
		putenv('RADAPTOR_RUNTIME=swoole');
		putenv('SWOOLE_PERSISTENT_REDIS_CONNECTION');

		$storage = new RedisSessionStorage();
		$method = new ReflectionMethod(RedisSessionStorage::class, 'shouldUsePersistentConnection');
		$method->setAccessible(true);

		$this->assertTrue($method->invoke($storage));
	}

	public function testPersistentRedisConnectionsCanBeDisabledByEnvInSwooleRuntime(): void
	{
		putenv('RADAPTOR_RUNTIME=swoole');
		putenv('SWOOLE_PERSISTENT_REDIS_CONNECTION=false');

		$storage = new RedisSessionStorage();
		$method = new ReflectionMethod(RedisSessionStorage::class, 'shouldUsePersistentConnection');
		$method->setAccessible(true);

		$this->assertFalse($method->invoke($storage));
	}
}
