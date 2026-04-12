<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class CLITrustPolicyTest extends TestCase
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

	public function testNonCliContextIsNeverTrusted(): void
	{
		$this->assertFalse(CLITrustPolicy::isTrustedOperatorCli(false, false));
		$this->assertFalse(CLITrustPolicy::isTrustedOperatorCli(false, true));
	}

	public function testDirectCliWithoutBridgeIsTrusted(): void
	{
		$this->assertTrue(CLITrustPolicy::isTrustedOperatorCli(true, false));
	}

	public function testBridgedCliIsNotTrustedOperatorCli(): void
	{
		$this->assertFalse(CLITrustPolicy::isTrustedOperatorCli(true, true));
	}

	public function testCliWithExpiredWebRunnerPayloadIsNotTrustedOperatorCli(): void
	{
		TestHelperEnvironment::setEnvironmentVariable('APP_CLI_RUNNER_SIGNING_SECRET', 'test-secret');

		$user_id = 123;
		$timestamp = time() - 600;
		$nonce = str_repeat('b', 32);
		$signature = $this->invokeBridgeMethod('signPayload', [$user_id, $timestamp, $nonce]);

		TestHelperEnvironment::setEnvironmentVariable('RADAPTOR_WEB_RUNNER_USER_ID', (string) $user_id);
		TestHelperEnvironment::setEnvironmentVariable('RADAPTOR_WEB_RUNNER_TS', (string) $timestamp);
		TestHelperEnvironment::setEnvironmentVariable('RADAPTOR_WEB_RUNNER_NONCE', $nonce);
		TestHelperEnvironment::setEnvironmentVariable('RADAPTOR_WEB_RUNNER_SIG', $signature);

		$this->assertTrue(CLITrustPolicy::hasWebRunnerBridge());
		$this->assertFalse(CLITrustPolicy::isTrustedOperatorCli(true));
	}

	private function invokeBridgeMethod(string $method, array $arguments): mixed
	{
		$reflection = new ReflectionMethod(CLIWebRunnerUserBridge::class, $method);
		$reflection->setAccessible(true);

		return $reflection->invokeArgs(null, $arguments);
	}
}
