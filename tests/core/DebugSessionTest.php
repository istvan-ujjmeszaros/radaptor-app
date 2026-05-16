<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class DebugSessionTest extends TestCase
{
	private string|false $_previousEnvironment;

	protected function setUp(): void
	{
		RequestContextHolder::initializeRequest();
		$this->_previousEnvironment = getenv('ENVIRONMENT');
		putenv('ENVIRONMENT=development');
	}

	protected function tearDown(): void
	{
		if ($this->_previousEnvironment === false) {
			putenv('ENVIRONMENT');

			return;
		}

		putenv('ENVIRONMENT=' . $this->_previousEnvironment);
	}

	public function testBeginIfRequestedEnablesDebugInDevelopmentEnvironmentWithoutLoggedInUser(): void
	{
		RequestContextHolder::initializeRequest(server: ['HTTP_RADAPTOR_DEBUG' => '1']);

		DebugSession::beginIfRequested();

		$this->assertTrue(DebugSession::isEnabled());
		$this->assertStringStartsWith('dbg_', DebugSession::sessionId());
		$this->assertStringStartsWith('req_', DebugSession::requestId());
		$this->assertSame(['tree', 'dommap', 'timings'], DebugSession::features());
	}

	public function testBeginIfRequestedStaysDisabledWithoutHeader(): void
	{
		DebugSession::beginIfRequested();

		$this->assertFalse(DebugSession::isEnabled());
	}

	public function testIsCacheBypassRequestedNeedsHeaderAndDebugConfig(): void
	{
		$previousDebugInfo = getenv('DEV_APP_DEBUG_INFO');

		try {
			putenv('DEV_APP_DEBUG_INFO=1');

			RequestContextHolder::initializeRequest(server: ['HTTP_RADAPTOR_DEBUG' => '1']);
			$this->assertTrue(DebugSession::isCacheBypassRequested());

			RequestContextHolder::initializeRequest();
			$this->assertFalse(DebugSession::isCacheBypassRequested());

			// With the debug config off the header must not bypass the cache,
			// otherwise any anonymous request could disable persistent caching.
			putenv('DEV_APP_DEBUG_INFO=0');
			RequestContextHolder::initializeRequest(server: ['HTTP_RADAPTOR_DEBUG' => '1']);
			$this->assertFalse(DebugSession::isCacheBypassRequested());
		} finally {
			if ($previousDebugInfo === false) {
				putenv('DEV_APP_DEBUG_INFO');
			} else {
				putenv('DEV_APP_DEBUG_INFO=' . $previousDebugInfo);
			}
		}
	}
}
