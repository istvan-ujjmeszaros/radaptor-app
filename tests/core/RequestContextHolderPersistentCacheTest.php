<?php

use PHPUnit\Framework\TestCase;

class RequestContextHolderPersistentCacheTest extends TestCase
{
	protected function setUp(): void
	{
		putenv('APP_PERSISTENT_CACHE_ENABLED=true');
		RequestContextHolder::initializeRequest();
	}

	protected function tearDown(): void
	{
		putenv('APP_PERSISTENT_CACHE_ENABLED');
	}

	public function testPersistentCacheWriteIsEnabledByDefault(): void
	{
		$this->assertTrue(RequestContextHolder::isPersistentCacheWriteEnabled());
	}

	public function testDisablePersistentCacheWriteForCurrentRequest(): void
	{
		RequestContextHolder::disablePersistentCacheWrite();

		$this->assertFalse(RequestContextHolder::isPersistentCacheWriteEnabled());
	}

	public function testEnablePersistentCacheWriteForCurrentRequest(): void
	{
		RequestContextHolder::disablePersistentCacheWrite();
		$this->assertFalse(RequestContextHolder::isPersistentCacheWriteEnabled());

		RequestContextHolder::enablePersistentCacheWrite();

		$this->assertTrue(RequestContextHolder::isPersistentCacheWriteEnabled());
	}

	public function testInitializeRequestResetsPersistentCacheWriteFlagToDefault(): void
	{
		RequestContextHolder::disablePersistentCacheWrite();
		$this->assertFalse(RequestContextHolder::isPersistentCacheWriteEnabled());

		RequestContextHolder::initializeRequest();

		$this->assertTrue(RequestContextHolder::isPersistentCacheWriteEnabled());
	}

	public function testConfigCanGloballyDisablePersistentCacheWrite(): void
	{
		putenv('APP_PERSISTENT_CACHE_ENABLED=false');
		RequestContextHolder::initializeRequest();

		$this->assertFalse(RequestContextHolder::isPersistentCacheWriteEnabled());
	}
}
