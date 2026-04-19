<?php

use PHPUnit\Framework\TestCase;

class DbPersistentConnectionTest extends TestCase
{
	protected function tearDown(): void
	{
		putenv('RADAPTOR_RUNTIME');
		putenv('SWOOLE_PERSISTENT_DB_CONNECTION');
	}

	public function testPersistentDbConnectionsDefaultToEnabledInSwooleRuntime(): void
	{
		putenv('RADAPTOR_RUNTIME=swoole');
		putenv('SWOOLE_PERSISTENT_DB_CONNECTION');

		$method = new ReflectionMethod(Db::class, 'shouldUsePersistentConnections');

		$this->assertTrue($method->invoke(null));
	}

	public function testPersistentDbConnectionsCanBeDisabledByEnvInSwooleRuntime(): void
	{
		putenv('RADAPTOR_RUNTIME=swoole');
		putenv('SWOOLE_PERSISTENT_DB_CONNECTION=0');

		$method = new ReflectionMethod(Db::class, 'shouldUsePersistentConnections');

		$this->assertFalse($method->invoke(null));
	}

	public function testRecoverableConnectionErrorsDetectedByDriverCode(): void
	{
		$exception = new PDOException('General error');
		$exception->errorInfo = ['HY000', 2006, 'MySQL server has gone away'];

		$method = new ReflectionMethod(Db::class, 'isRecoverableConnectionError');

		$this->assertTrue($method->invoke(null, $exception));
	}
}
