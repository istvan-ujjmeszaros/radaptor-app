<?php

use PHPUnit\Framework\TestCase;

/**
 * Tests for RedisSessionStorage.
 *
 * Requires a Redis instance at SESSION_REDIS_HOST (defaults to 'redis-test').
 * Tests are skipped gracefully if the host is unreachable.
 */
class RedisSessionStorageTest extends TestCase
{
	private RedisSessionStorage $_storage;

	protected function setUp(): void
	{
		// Point tests at the isolated test Redis instance.
		putenv('SESSION_REDIS_HOST=redis-test');
		putenv('SESSION_REDIS_PORT=6379');

		$storage = new RedisSessionStorage();

		try {
			$storage->assertAvailable();
		} catch (RuntimeException $e) {
			$this->markTestSkipped('redis-test not available (' . $e->getMessage() . ')');
		}

		RequestContextHolder::initializeRequest();

		$this->_storage = $storage;
	}

	protected function tearDown(): void
	{
		// Clean up: destroy the session so keys don't leak between tests.
		try {
			$this->_storage->destroy();
		} catch (RuntimeException) {
			// Ignore if Redis vanished mid-test.
		}
	}

	// =========================================================================
	// Session lifecycle
	// =========================================================================

	public function testStartCreatesNewSessionId(): void
	{
		$this->_storage->start();

		$ctx = RequestContextHolder::current();
		$this->assertNotNull($ctx->sessionId);
		$this->assertNotEmpty($ctx->sessionId);
	}

	public function testStartIsIdempotent(): void
	{
		$this->_storage->start();
		$firstId = RequestContextHolder::current()->sessionId;

		$this->_storage->start();
		$secondId = RequestContextHolder::current()->sessionId;

		$this->assertSame($firstId, $secondId);
	}

	// =========================================================================
	// get / set
	// =========================================================================

	public function testGetReturnsNullForMissingKey(): void
	{
		$this->assertNull($this->_storage->get('nonexistent_key'));
	}

	public function testSetAndGetStringKey(): void
	{
		$this->_storage->set('username', 'alice');

		$this->assertSame('alice', $this->_storage->get('username'));
	}

	public function testSetAndGetNestedArrayKey(): void
	{
		$this->_storage->set(['user', 'profile', 'name'], 'bob');

		$this->assertSame('bob', $this->_storage->get(['user', 'profile', 'name']));
	}

	public function testGetEmptyArrayReturnsFullSessionData(): void
	{
		$this->_storage->set('key1', 'value1');
		$this->_storage->set('key2', 'value2');

		$all = $this->_storage->get([]);

		$this->assertIsArray($all);
		$this->assertArrayHasKey('key1', $all);
		$this->assertArrayHasKey('key2', $all);
	}

	// =========================================================================
	// isset
	// =========================================================================

	public function testIssetReturnsTrueForExistingKey(): void
	{
		$this->_storage->set('mykey', 'myvalue');

		$this->assertTrue($this->_storage->isset('mykey'));
	}

	public function testIssetReturnsFalseForMissingKey(): void
	{
		$this->assertFalse($this->_storage->isset('ghost'));
	}

	public function testIssetForNestedPath(): void
	{
		$this->_storage->set(['a', 'b'], 'deep');

		$this->assertTrue($this->_storage->isset(['a', 'b']));
		$this->assertFalse($this->_storage->isset(['a', 'c']));
	}

	// =========================================================================
	// unset
	// =========================================================================

	public function testUnsetRemovesKey(): void
	{
		$this->_storage->set('temp', 'gone');
		$this->_storage->unset('temp');

		$this->assertFalse($this->_storage->isset('temp'));
	}

	public function testUnsetNestedKeyLeavesParent(): void
	{
		$this->_storage->set(['parent', 'child'], 'value');
		$this->_storage->unset(['parent', 'child']);

		$this->assertFalse($this->_storage->isset(['parent', 'child']));
		// Parent key still exists (as an empty array).
		$this->assertTrue($this->_storage->isset('parent'));
	}

	// =========================================================================
	// commit / persistence
	// =========================================================================

	public function testCommitPersistsToRedis(): void
	{
		$this->_storage->set('persisted', 'yes');

		// Capture the session ID before creating a fresh storage instance.
		$sessionId = RequestContextHolder::current()->sessionId;

		// Simulate a fresh request with the same session cookie.
		RequestContextHolder::initializeRequest();
		RequestContextHolder::current()->COOKIE[session_name()] = $sessionId;

		$fresh = new RedisSessionStorage();
		$fresh->start();

		$this->assertSame('yes', $fresh->get('persisted'));

		// Clean up via fresh instance.
		$fresh->destroy();
	}

	public function testStartResumesSessionFromSuperglobalCookieWhenCtxCookieIsEmpty(): void
	{
		// First request: start a session normally and store data.
		$this->_storage->start();
		$this->_storage->set('fpm_test_key', 'test_value');
		$sessionId = RequestContextHolder::current()->sessionId;

		// Simulate FPM second request: ctx->COOKIE is empty (never populated),
		// but $_COOKIE carries the PHPSESSID from the browser.
		RequestContextHolder::initializeRequest();
		// ctx->COOKIE intentionally left empty — this is the FPM default.
		$_COOKIE[session_name()] = $sessionId;

		$fresh = new RedisSessionStorage();
		$fresh->start();

		$this->assertSame(
			$sessionId,
			RequestContextHolder::current()->sessionId,
			'start() must reuse the session ID from $_COOKIE when ctx->COOKIE is empty'
		);
		$this->assertSame(
			'test_value',
			$fresh->get('fpm_test_key'),
			'Session data must be loaded from Redis using the cookie-provided session ID'
		);

		$fresh->destroy();
		unset($_COOKIE[session_name()]);
	}

	// =========================================================================
	// destroy
	// =========================================================================

	public function testDestroyDeletesSessionFromRedis(): void
	{
		$this->_storage->set('todelete', 'bye');
		$sessionId = RequestContextHolder::current()->sessionId;

		$this->_storage->destroy();

		// Start a new storage instance with the same session ID.
		RequestContextHolder::initializeRequest();
		RequestContextHolder::current()->COOKIE[session_name()] = $sessionId;

		$fresh = new RedisSessionStorage();
		$fresh->start();

		$this->assertNull($fresh->get('todelete'));

		// The session data should be empty.
		$this->assertEmpty($fresh->get([]));
	}

	// =========================================================================
	// Security: invalid session ID in cookie
	// =========================================================================

	public function testInvalidSessionIdInCookieGeneratesNewOne(): void
	{
		$malicious = '../../etc/passwd';
		RequestContextHolder::current()->COOKIE[session_name()] = $malicious;

		$this->_storage->start();

		$sessionId = RequestContextHolder::current()->sessionId;

		// Must not use the malicious value.
		$this->assertNotSame($malicious, $sessionId);
		// Must be a valid hex string (32 chars from bin2hex(random_bytes(16))).
		$this->assertMatchesRegularExpression('/^[A-Za-z0-9,-]+$/', $sessionId);
	}
}
