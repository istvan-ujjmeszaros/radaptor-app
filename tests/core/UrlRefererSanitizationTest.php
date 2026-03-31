<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class UrlRefererSanitizationTest extends TestCase
{
	protected function setUp(): void
	{
		RequestContextHolder::initializeRequest(server: [
			'REQUEST_URI' => '/admin/',
			'HTTP_HOST' => 'localhost',
			'SERVER_PORT' => '80',
			'SERVER_PROTOCOL' => 'HTTP/1.1',
			'HTTPS' => '',
		]);
	}

	public function testSanitizeRefererUrlStripsNestedRefererAndLogoutParams(): void
	{
		$url = 'http://localhost/admin/?context=user&amp;event=logout&amp;referer=http%3A%2F%2Flocalhost%2Fadmin%2F';

		$this->assertSame(
			'http://localhost/admin/',
			Url::sanitizeRefererUrl($url)
		);
	}

	public function testSanitizeRefererUrlPreservesNonLogoutContextParams(): void
	{
		$url = 'http://localhost/admin/?context=ticket&event=description&id=42&referer=http%3A%2F%2Flocalhost%2Fadmin%2F';

		$this->assertSame(
			'http://localhost/admin/?context=ticket&event=description&id=42',
			Url::sanitizeRefererUrl($url)
		);
	}

	public function testGetCurrentUrlForRefererNormalizesBrokenLogoutLoopUrl(): void
	{
		RequestContextHolder::initializeRequest(server: [
			'REQUEST_URI' => '/admin/?context=user&amp;event=logout&amp;referer=http%3A%2F%2Flocalhost%2Fadmin%2F',
			'HTTP_HOST' => 'localhost',
			'SERVER_PORT' => '80',
			'SERVER_PROTOCOL' => 'HTTP/1.1',
			'HTTPS' => '',
		]);

		$this->assertSame(
			'http://localhost/admin/',
			Url::getCurrentUrlForReferer()
		);
	}
}
