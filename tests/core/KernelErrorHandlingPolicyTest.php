<?php

use PHPUnit\Framework\TestCase;

class KernelErrorHandlingPolicyTest extends TestCase
{
	/**
	 * @param array<int, mixed> $args
	 */
	private function callKernelPrivateStatic(string $method, array $args = []): mixed
	{
		$reflection = new ReflectionClass(Kernel::class);
		$target = $reflection->getMethod($method);

		return $target->invokeArgs(null, $args);
	}

	public function testEntitySaveExceptionMapsTo422(): void
	{
		$result = $this->callKernelPrivateStatic(
			'mapExceptionToApiError',
			[new EntitySaveException('save failed')]
		);

		$this->assertEquals(422, $result['status']);
		$this->assertEquals('entity_save_failed', $result['code']);
	}

	public function testGenericExceptionMapsTo500(): void
	{
		$result = $this->callKernelPrivateStatic(
			'mapExceptionToApiError',
			[new RuntimeException('boom')]
		);

		$this->assertEquals(500, $result['status']);
		$this->assertEquals('internal_error', $result['code']);
	}

	public function testBuildApiErrorPayloadHidesDetailsWhenDisabled(): void
	{
		$payload = $this->callKernelPrivateStatic(
			'buildApiErrorPayload',
			['internal_error', 'Generic', 'trace-123', new RuntimeException('boom'), false]
		);

		$this->assertArrayHasKey('error', $payload);
		$this->assertEquals('internal_error', $payload['error']['code']);
		$this->assertEquals('trace-123', $payload['error']['trace_id']);
		$this->assertArrayNotHasKey('details', $payload['error']);
	}

	public function testBuildApiErrorPayloadIncludesDetailsWhenEnabled(): void
	{
		$payload = $this->callKernelPrivateStatic(
			'buildApiErrorPayload',
			['entity_save_failed', 'Generic', 'trace-456', new EntitySaveException('db fail', EntityUser::class, ['k' => 'v']), true]
		);

		$this->assertArrayHasKey('details', $payload['error']);
		$this->assertEquals('EntitySaveException', $payload['error']['details']['type']);
		$this->assertEquals('db fail', $payload['error']['details']['message']);
		$this->assertEquals(EntityUser::class, $payload['error']['details']['entityClass']);
	}

	public function testShouldExposeDetailsInDevelopmentRegardlessOfRole(): void
	{
		$previous = getenv('ENVIRONMENT');
		putenv('ENVIRONMENT=development');

		try {
			$this->assertTrue($this->callKernelPrivateStatic('shouldExposeErrorDetails'));
		} finally {
			if ($previous === false) {
				putenv('ENVIRONMENT');
			} else {
				putenv('ENVIRONMENT=' . $previous);
			}
		}
	}

	public function testShouldNotExposeDetailsByDefaultOutsideDevelopment(): void
	{
		$previous = getenv('ENVIRONMENT');
		putenv('ENVIRONMENT=production');

		try {
			$this->assertFalse($this->callKernelPrivateStatic('shouldExposeErrorDetails'));
		} finally {
			if ($previous === false) {
				putenv('ENVIRONMENT');
			} else {
				putenv('ENVIRONMENT=' . $previous);
			}
		}
	}

	public function testSafeGetSessionDataForLoggingReturnsEmptyWhenSessionStorageIsUnavailable(): void
	{
		$holder = new ReflectionClass(SessionContextHolder::class);
		$initialized = $holder->getProperty('initialized');
		$previous = $initialized->getValue();

		try {
			$initialized->setValue(null, false);
			$result = $this->callKernelPrivateStatic('safeGetSessionDataForLogging');
			$this->assertSame([], $result);
		} finally {
			$initialized->setValue(null, $previous);
		}
	}
}
