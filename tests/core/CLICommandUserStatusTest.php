<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class CLICommandUserStatusTest extends TestCase
{
	public function testWebParamsDoNotRequireMeaninglessUsername(): void
	{
		$command = new CLICommandUserStatus();

		$this->assertSame(
			[
				['name' => 'json', 'label' => 'JSON output', 'type' => 'flag'],
			],
			$command->getWebParams()
		);
	}
}
