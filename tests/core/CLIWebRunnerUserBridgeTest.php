<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class CLIWebRunnerUserBridgeTest extends TestCase
{
	/** @var list<string> */
	private array $environmentKeys = [
		'APP_CLI_RUNNER_SIGNING_SECRET',
		'RADAPTOR_WEB_RUNNER_USER_ID',
		'RADAPTOR_WEB_RUNNER_TS',
		'RADAPTOR_WEB_RUNNER_NONCE',
		'RADAPTOR_WEB_RUNNER_SIG',
	];

	protected function tearDown(): void
	{
		foreach ($this->environmentKeys as $key) {
			TestHelperEnvironment::revertEnvironmentVariable($key);
		}
	}

	public function testSignedEnvironmentRoundTripsUserId(): void
	{
		TestHelperEnvironment::setEnvironmentVariable('APP_CLI_RUNNER_SIGNING_SECRET', 'test-secret');

		foreach (CLIWebRunnerUserBridge::buildEnvironmentForUserId(123) as $key => $value) {
			TestHelperEnvironment::setEnvironmentVariable($key, $value);
		}

		$this->assertSame(123, CLIWebRunnerUserBridge::resolveTrustedUserIdFromEnvironment());
	}

	public function testExpiredEnvironmentIsRejected(): void
	{
		TestHelperEnvironment::setEnvironmentVariable('APP_CLI_RUNNER_SIGNING_SECRET', 'test-secret');

		$userId = 123;
		$timestamp = time() - 600;
		$nonce = str_repeat('a', 32);
		$signature = $this->invokeBridgeMethod('signPayload', [$userId, $timestamp, $nonce]);

		TestHelperEnvironment::setEnvironmentVariable('RADAPTOR_WEB_RUNNER_USER_ID', (string) $userId);
		TestHelperEnvironment::setEnvironmentVariable('RADAPTOR_WEB_RUNNER_TS', (string) $timestamp);
		TestHelperEnvironment::setEnvironmentVariable('RADAPTOR_WEB_RUNNER_NONCE', $nonce);
		TestHelperEnvironment::setEnvironmentVariable('RADAPTOR_WEB_RUNNER_SIG', $signature);

		$this->assertNull(CLIWebRunnerUserBridge::resolveTrustedUserIdFromEnvironment());
	}

	public function testExpiredEnvironmentStillMarksProcessAsWebRunner(): void
	{
		TestHelperEnvironment::setEnvironmentVariable('APP_CLI_RUNNER_SIGNING_SECRET', 'test-secret');

		$userId = 123;
		$timestamp = time() - 600;
		$nonce = str_repeat('a', 32);
		$signature = $this->invokeBridgeMethod('signPayload', [$userId, $timestamp, $nonce]);

		TestHelperEnvironment::setEnvironmentVariable('RADAPTOR_WEB_RUNNER_USER_ID', (string) $userId);
		TestHelperEnvironment::setEnvironmentVariable('RADAPTOR_WEB_RUNNER_TS', (string) $timestamp);
		TestHelperEnvironment::setEnvironmentVariable('RADAPTOR_WEB_RUNNER_NONCE', $nonce);
		TestHelperEnvironment::setEnvironmentVariable('RADAPTOR_WEB_RUNNER_SIG', $signature);

		$this->assertTrue(CLIWebRunnerUserBridge::isWebRunnerProcess());
		$this->assertNull(CLIWebRunnerUserBridge::resolveTrustedUserIdFromEnvironment());
	}

	public function testCliApplicationIdentifierOverrideDoesNotInvalidateSignature(): void
	{
		TestHelperEnvironment::setEnvironmentVariable('APP_CLI_RUNNER_SIGNING_SECRET', 'test-secret');

		foreach (CLIWebRunnerUserBridge::buildEnvironmentForUserId(123) as $key => $value) {
			TestHelperEnvironment::setEnvironmentVariable($key, $value);
		}

		TestHelperEnvironment::setEnvironmentVariable('APP_APPLICATION_IDENTIFIER', 'Radaptor CLI');

		$this->assertSame(123, CLIWebRunnerUserBridge::resolveTrustedUserIdFromEnvironment());
	}

	private function invokeBridgeMethod(string $method, array $arguments): mixed
	{
		$reflection = new ReflectionMethod(CLIWebRunnerUserBridge::class, $method);
		$reflection->setAccessible(true);

		return $reflection->invokeArgs(null, $arguments);
	}
}
