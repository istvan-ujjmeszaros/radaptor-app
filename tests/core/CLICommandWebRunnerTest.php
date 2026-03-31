<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class CLICommandWebRunnerTest extends TestCase
{
	public function testExecutePassesNamedOptionsAsKeyValueTokens(): void
	{
		$result = CLICommandWebRunner::execute(
			'db:show',
			'migrations',
			['limit' => '1', 'offset' => '2'],
			['json'],
			30
		);

		$this->assertTrue($result['ok'], $result['error'] !== '' ? $result['error'] : $result['output']);
		$this->assertIsArray($result['json_data']);
		$this->assertSame(1, $result['json_data']['limit'] ?? null);
		$this->assertSame(2, $result['json_data']['offset'] ?? null);
		$this->assertLessThanOrEqual(1, count($result['json_data']['rows'] ?? []));
	}
}
