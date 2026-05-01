<?php

declare(strict_types=1);

final class CLICommandMcpTokenCreateTest extends TransactionedTestCase
{
	private array $originalArgv;

	protected function setUp(): void
	{
		parent::setUp();
		global $argv;
		$this->originalArgv = $argv ?? [];
	}

	protected function tearDown(): void
	{
		global $argv;
		$argv = $this->originalArgv;
		parent::tearDown();
	}

	public function testCliOptionHelperParsesDaysZeroAsLiteralZero(): void
	{
		// Locks in the contract that the McpTokenCreate command's removed
		// hand-parsed argv workaround relied on: `--days 0` must reach the
		// command as the literal string "0", not be replaced by the default.
		global $argv;
		$argv = ['radaptor', 'mcp:token-create', 'someone', '--days', '0'];

		$this->assertSame('0', CLIOptionHelper::getOption('days', '90'));
	}

	public function testCliOptionHelperFallsBackToDefaultWhenDaysOmitted(): void
	{
		global $argv;
		$argv = ['radaptor', 'mcp:token-create', 'someone'];

		$this->assertSame('90', CLIOptionHelper::getOption('days', '90'));
	}

	public function testTokenCreateCommandHonorsDaysZeroAsNoExpiry(): void
	{
		$user = EntityUser::saveFromArray([
			'username' => 'mcp_cli_' . uniqid(),
			'password' => UserBase::encodePassword('secret'),
			'is_active' => 1,
		]);
		$user_id = (int) $user->pkey();
		$username = (string) $user->username;

		global $argv;
		$argv = ['radaptor', 'mcp:token-create', $username, '--days', '0', '--json'];

		ob_start();

		try {
			(new CLICommandMcpTokenCreate())->run();
		} finally {
			$output = ob_get_clean();
		}

		$decoded = json_decode($output, true);
		$this->assertIsArray($decoded);
		$this->assertSame('success', $decoded['status']);
		$this->assertSame($user_id, $decoded['user_id']);
		$this->assertNull($decoded['expires_at'], '--days 0 must produce no expiry');
	}

	public function testTokenCreateCommandRejectsNonNumericDays(): void
	{
		$user = EntityUser::saveFromArray([
			'username' => 'mcp_cli_bad_' . uniqid(),
			'password' => UserBase::encodePassword('secret'),
			'is_active' => 1,
		]);

		global $argv;
		$argv = ['radaptor', 'mcp:token-create', (string) $user->username, '--days', 'abc', '--json'];

		ob_start();

		try {
			(new CLICommandMcpTokenCreate())->run();
		} finally {
			$output = ob_get_clean();
		}

		$decoded = json_decode($output, true);
		$this->assertIsArray($decoded);
		$this->assertSame('error', $decoded['status']);
	}
}
