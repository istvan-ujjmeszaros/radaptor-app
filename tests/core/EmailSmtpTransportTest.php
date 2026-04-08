<?php

declare(strict_types=1);

final class EmailSmtpTransportTest extends TransactionedTestCase
{
	public function testResolveEffectiveSettingsUsesMailpitInNonProduction(): void
	{
		$settings = EmailSmtpTransport::resolveEffectiveSettings('test');

		$this->assertTrue($settings['using_catcher']);
		$this->assertSame('mailpit', $settings['host']);
		$this->assertSame(1025, $settings['port']);
		$this->assertSame('no-reply@localhost', $settings['from_address']);
	}

	public function testResolveEffectiveSettingsRequiresSmtpHostInProduction(): void
	{
		$this->expectException(EmailJobProcessingException::class);
		$this->expectExceptionMessage('EMAIL_SMTP_HOST is not configured.');

		EmailSmtpTransport::resolveEffectiveSettings('production');
	}
}
