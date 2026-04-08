<?php

declare(strict_types=1);

final class ConfigTest extends TransactionedTestCase
{
	public function testEnvironmentNotOverwritten(): void
	{
		$this->assertNotEquals('overwritten', Config::APP_DOMAIN_CONTEXT->value());
	}

	public function testEnvironmentOverwrite(): void
	{
		TestHelperEnvironment::setEnvironmentVariable('APP_DOMAIN_CONTEXT', 'overwritten');

		$this->assertEquals('overwritten', Config::APP_DOMAIN_CONTEXT->value());

		TestHelperEnvironment::revertEnvironmentVariable('APP_DOMAIN_CONTEXT');
	}
}
