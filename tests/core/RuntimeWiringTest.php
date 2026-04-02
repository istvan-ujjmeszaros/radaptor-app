<?php

use PHPUnit\Framework\TestCase;

class RuntimeWiringTest extends TestCase
{
	public function testBootstrapDelegatesToResolvedFrameworkBootstrap(): void
	{
		$bootstrap = file_get_contents(DEPLOY_ROOT . 'bootstrap/bootstrap.php');

		$this->assertIsString($bootstrap);
		$this->assertStringContainsString("require_once __DIR__ . '/bootstrap.package_locator.php';", $bootstrap);
		$this->assertStringContainsString("radaptorAppBootstrapRequireFrameworkBootstrap('bootstrap.php', __DIR__);", $bootstrap);
	}

	public function testCliEntrypointEnsuresFrameworkBootstrapBeforeDelegating(): void
	{
		$cliEntrypoint = file_get_contents(DEPLOY_ROOT . 'radaptor.php');

		$this->assertIsString($cliEntrypoint);
		$this->assertStringContainsString("require_once __DIR__ . '/bootstrap/bootstrap.package_locator.php';", $cliEntrypoint);
		$this->assertStringContainsString('radaptorAppBootstrapEnsureCliFrameworkAvailable(__DIR__);', $cliEntrypoint);
		$this->assertStringContainsString("require_once __DIR__ . '/bootstrap/bootstrap.php';", $cliEntrypoint);
	}

	public function testHttpEntrypointBootstrapsThroughAppShim(): void
	{
		$httpEntrypoint = file_get_contents(DEPLOY_ROOT . 'public/www/index.php');

		$this->assertIsString($httpEntrypoint);
		$this->assertStringContainsString("require_once dirname(__DIR__, 2) . '/bootstrap/bootstrap.php';", $httpEntrypoint);
		$this->assertStringContainsString('Kernel::initialize();', $httpEntrypoint);
		$this->assertStringContainsString('EventResolver::dispatch();', $httpEntrypoint);
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
