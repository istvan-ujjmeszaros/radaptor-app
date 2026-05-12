<?php

declare(strict_types=1);

final class CmsSiteSnapshotServiceTest extends TransactionedTestCase
{
	public function testTableCommentTokenParsingIsExplicitAndTrimmed(): void
	{
		$this->assertSame(
			['__noaudit', '__noexport'],
			$this->parseTableCommentTokens(' __noaudit, __NOEXPORT, __noexport , ')
		);
		$this->assertFalse($this->tableCommentHasToken('__noaudit', '__noexport'));
		$this->assertTrue($this->tableCommentHasToken('__noaudit, __noexport', '__noexport'));
	}

	public function testExportIncludesMigrationAndSeedTablesByDefault(): void
	{
		$snapshot = CmsSiteSnapshotService::exportSnapshot(true);

		$this->assertArrayHasKey('migrations', $snapshot['tables']);
		$this->assertArrayHasKey('seeds', $snapshot['tables']);
		$this->assertArrayHasKey('environment', $snapshot);
		$this->assertArrayHasKey('excluded_tables', $snapshot);
		$this->assertArrayNotHasKey('excluded_operational_tables', $snapshot);
	}

	public function testDryRunReportsEnvironmentMismatchWithoutBlockingValidation(): void
	{
		$snapshot = $this->snapshotWithDifferentEnvironment();

		$result = CmsSiteSnapshotService::importSnapshot($snapshot, true, true);

		$this->assertSame('success', $result['status']);
		$this->assertFalse($result['applied']);
		$this->assertSame('mismatch', $result['environment_check']['status']);
	}

	public function testApplyBlocksEnvironmentMismatchBeforeMutation(): void
	{
		$snapshot = $this->snapshotWithDifferentEnvironment();

		$result = CmsSiteSnapshotService::importSnapshot($snapshot, false, true);

		$this->assertSame('error', $result['status']);
		$this->assertFalse($result['applied']);
		$this->assertSame('mismatch', $result['environment_check']['status']);
		$this->assertContains(
			'Snapshot environment does not match the current environment. Re-run with --allow-environment-mismatch only if this restore target is intentional.',
			$result['errors']
		);
	}

	public function testAllowEnvironmentMismatchLeavesOtherApplyGuardsInCharge(): void
	{
		$snapshot = $this->snapshotWithDifferentEnvironment();

		$result = CmsSiteSnapshotService::importSnapshot($snapshot, false, false, true);

		$this->assertSame('error', $result['status']);
		$this->assertFalse($result['applied']);
		$this->assertSame('mismatch', $result['environment_check']['status']);
		$this->assertTrue($result['environment_check']['allowed']);
		$this->assertContains('Refusing destructive import without --replace.', $result['errors']);
		$this->assertNotContains(
			'Snapshot environment does not match the current environment. Re-run with --allow-environment-mismatch only if this restore target is intentional.',
			$result['errors']
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private function snapshotWithDifferentEnvironment(): array
	{
		$snapshot = CmsSiteSnapshotService::exportSnapshot(true);
		$snapshot['environment']['database']['dbname'] = '__different_database__';

		return $snapshot;
	}

	/**
	 * @return list<string>
	 */
	private function parseTableCommentTokens(string $comment): array
	{
		$method = new ReflectionMethod(CmsSiteSnapshotService::class, 'parseTableCommentTokens');
		$method->setAccessible(true);

		return $method->invoke(null, $comment);
	}

	private function tableCommentHasToken(string $comment, string $token): bool
	{
		$method = new ReflectionMethod(CmsSiteSnapshotService::class, 'tableCommentHasToken');
		$method->setAccessible(true);

		return $method->invoke(null, $comment, $token);
	}
}
