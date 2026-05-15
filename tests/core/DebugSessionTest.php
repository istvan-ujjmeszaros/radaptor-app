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
		$this->assertSame(['tree', 'dommap', 'messages', 'timings'], DebugSession::features());
	}

	public function testBeginIfRequestedStaysDisabledWithoutHeader(): void
	{
		DebugSession::beginIfRequested();

		$this->assertFalse(DebugSession::isEnabled());
	}
}
