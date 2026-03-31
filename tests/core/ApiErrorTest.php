<?php

use PHPUnit\Framework\TestCase;

class ApiErrorTest extends TestCase
{
	public function testToArrayIncludesCodeAndMessage(): void
	{
		$error = new ApiError('VALIDATION', 'Validation failed');
		$array = $error->toArray();

		$this->assertEquals('VALIDATION', $array['code']);
		$this->assertEquals('Validation failed', $array['message']);
	}

	public function testToArrayStripsEmptyFieldsAndDetails(): void
	{
		$error = new ApiError('GENERIC', 'Error');
		$array = $error->toArray();

		$this->assertArrayNotHasKey('fields', $array);
		$this->assertArrayNotHasKey('details', $array);
	}

	public function testToArrayIncludesFieldsWhenSet(): void
	{
		$error = new ApiError('VALIDATION', 'Validation failed', ['name' => 'Name is required']);
		$array = $error->toArray();

		$this->assertArrayHasKey('fields', $array);
		$this->assertEquals(['name' => 'Name is required'], $array['fields']);
	}

	public function testToArrayIncludesDetailsWhenSet(): void
	{
		$error = new ApiError('INTERNAL', 'Server error', [], ['trace' => 'stack info']);
		$array = $error->toArray();

		$this->assertArrayHasKey('details', $array);
		$this->assertEquals(['trace' => 'stack info'], $array['details']);
	}
}
