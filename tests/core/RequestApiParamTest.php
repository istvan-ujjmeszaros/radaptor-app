<?php

use PHPUnit\Framework\TestCase;

class RequestApiParamTest extends TestCase
{
	protected function setUp(): void
	{
		RequestContextHolder::initializeRequest();
	}

	public function testApiGetRequiredReturnsValue(): void
	{
		RequestContextHolder::current()->GET['name'] = 'John';

		$this->assertSame('John', Request::getRequired('name'));
	}

	public function testApiGetRequiredThrowsOnMissing(): void
	{
		$this->expectException(RequestParamException::class);
		$this->expectExceptionMessage('Missing GET param: missing');

		Request::getRequired('missing');
	}

	public function testApiGetOptionalReturnsDefault(): void
	{
		$this->assertSame('fallback', Request::getOptional('missing', 'fallback'));
	}

	public function testApiGetOptionalThrowsOnInvalidAllowedValue(): void
	{
		RequestContextHolder::current()->GET['status'] = 'unknown';

		$this->expectException(RequestParamException::class);
		$this->expectExceptionMessage('Invalid GET param: status');

		Request::getOptional('status', 'active', ['active', 'inactive']);
	}

	public function testApiPostRequiredThrowsOnInvalidAllowedValue(): void
	{
		RequestContextHolder::current()->POST['type'] = 'invalid';

		$this->expectException(RequestParamException::class);
		$this->expectExceptionMessage('Invalid POST param: type');

		Request::postRequired('type', ['admin', 'user']);
	}

	public function testApiAllowedValuesUseStrictComparison(): void
	{
		RequestContextHolder::current()->GET['id'] = '1';

		$this->expectException(RequestParamException::class);

		Request::getRequired('id', [1]);
	}

	public function testApiGetRequiredTreatsZeroStringAsPresent(): void
	{
		RequestContextHolder::current()->GET['parent_id'] = '0';

		$this->assertSame('0', Request::getRequired('parent_id'));
	}
}
