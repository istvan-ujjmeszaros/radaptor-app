<?php

use PHPUnit\Framework\TestCase;

class CacheTest extends TestCase
{
	protected function setUp(): void
	{
		// Ensure a clean cache state before each test
		Cache::flush();
	}

	public function testSetAndGet(): void
	{
		// Test setting and getting cache values
		Cache::set('Url', 'page_id_1', 'http://example.com/page');
		$this->assertEquals('http://example.com/page', Cache::get('Url', 'page_id_1'));
	}

	public function testIsset(): void
	{
		// Ensure the key does not exist initially
		$this->assertFalse(Cache::isset('Url', 'page_id_1'));

		// Set a value and check if it exists
		Cache::set('Url', 'page_id_1', 'Data');
		$this->assertTrue(Cache::isset('Url', 'page_id_1'));
	}

	public function testFlushSpecificContext(): void
	{
		// Set values in two different contexts
		Cache::set('Url', 'page_id_1', 'http://example.com/page');
		Cache::set('User', 'user_id_1', 'User Data');

		// Flush only the 'Url' context
		Cache::flush('Url');

		// Verify 'Url' context is flushed
		$this->assertNull(Cache::get('Url', 'page_id_1'));
		// Verify 'User' context is not flushed
		$this->assertEquals('User Data', Cache::get('User', 'user_id_1'));
	}

	public function testFlushAll(): void
	{
		// Set values in two contexts
		Cache::set('Url', 'page_id_1', 'http://example.com/page');
		Cache::set('User', 'user_id_1', 'User Data');

		// Flush all cache
		Cache::flush();

		// Verify all contexts are flushed
		$this->assertNull(Cache::get('Url', 'page_id_1'));
		$this->assertNull(Cache::get('User', 'user_id_1'));
	}

	public function testKeyGeneration(): void
	{
		// Test the generation of a cache key
		$params = ['user_id' => 123, 'settings' => ['theme' => 'dark']];
		$generatedKey = Cache::key($params);
		$expectedKey = '{"user_id":123,"settings":{"theme":"dark"}}';

		$this->assertEquals($expectedKey, $generatedKey);
	}

	public function testGetOnNeverSetKeyReturnsNull(): void
	{
		$this->assertNull(Cache::get('Url', 'nonexistent'));
	}

	public function testSetReturnsStoredValue(): void
	{
		$returned = Cache::set('Url', 'page_id_1', 'http://example.com/page');
		$this->assertEquals('http://example.com/page', $returned);
	}

	public function testSetOverwritesExistingValue(): void
	{
		Cache::set('Url', 'page_id_1', 'first');
		Cache::set('Url', 'page_id_1', 'second');
		$this->assertEquals('second', Cache::get('Url', 'page_id_1'));
	}

	public function testMultipleKeysInSameContext(): void
	{
		Cache::set('Roles', 'user:1:role:admin', true);
		Cache::set('Roles', 'user:2:role:admin', false);
		Cache::set('Roles', 'user:1:role:editor', false);

		$this->assertTrue(Cache::get('Roles', 'user:1:role:admin'));
		$this->assertFalse(Cache::get('Roles', 'user:2:role:admin'));
		$this->assertFalse(Cache::get('Roles', 'user:1:role:editor'));
	}

	public function testGetDoesNotCrossContexts(): void
	{
		Cache::set('ContextA', 'key', 'value-a');
		$this->assertNull(Cache::get('ContextB', 'key'));
	}

	public function testFlushNonExistentContextDoesNotError(): void
	{
		$this->expectNotToPerformAssertions();
		Cache::flush('NonExistentContext');
	}

	public function testIssetReturnsFalseAfterKeyIsOverwrittenWithNull(): void
	{
		Cache::set('Url', 'page_id_1', null);
		$this->assertFalse(Cache::isset('Url', 'page_id_1'));
	}
}
