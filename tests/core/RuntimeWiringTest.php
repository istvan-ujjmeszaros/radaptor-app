<?php

use PHPUnit\Framework\TestCase;

class RuntimeWiringTest extends TestCase
{
	public function testBootstrapUsesRedisSessionStorageByDefault(): void
	{
		$bootstrap = file_get_contents(DEPLOY_ROOT . 'radaptor/radaptor-framework/bootstrap.php');

		$this->assertIsString($bootstrap);
		$this->assertStringContainsString('new RedisSessionStorage();', $bootstrap);
		$this->assertStringContainsString('assertAvailable();', $bootstrap);
	}

	public function testSwooleEntrypointUsesRedisSessionStorage(): void
	{
		$swooleEntrypoint = file_get_contents(DEPLOY_ROOT . 'public/www/swoole_server.php');

		$this->assertIsString($swooleEntrypoint);
		$this->assertStringContainsString('new RedisSessionStorage();', $swooleEntrypoint);
		$this->assertStringContainsString('assertAvailable();', $swooleEntrypoint);
	}

	public function testSwooleQueueWorkerEntrypointUsesCoroutineRuntime(): void
	{
		$workerEntrypoint = file_get_contents(DEPLOY_ROOT . 'bin/swoole_queue_worker.php');

		$this->assertIsString($workerEntrypoint);
		$this->assertStringContainsString('\\Swoole\\Coroutine\\run(', $workerEntrypoint);
		$this->assertStringContainsString('\\Swoole\\Process::signal(SIGTERM', $workerEntrypoint);
		$this->assertStringContainsString('\\Swoole\\Process::signal(SIGINT', $workerEntrypoint);
		$this->assertStringContainsString('$stopRequested = true', $workerEntrypoint);
		$this->assertStringNotContainsString('runForever(', $workerEntrypoint);
	}
}
