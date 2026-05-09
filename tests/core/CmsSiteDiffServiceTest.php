<?php

declare(strict_types=1);

final class CmsSiteDiffServiceTest extends TransactionedTestCase
{
	public function testDiffSnapshotsReportsStableRowDeltas(): void
	{
		$baseline = $this->snapshot([
			['node_id' => 1, 'resource_name' => 'index.html', 'path' => '/', 'node_type' => 'webpage'],
			['node_id' => 2, 'resource_name' => 'old.html', 'path' => '/', 'node_type' => 'webpage'],
		]);
		$current = $this->snapshot([
			['node_id' => 1, 'resource_name' => 'index.html', 'path' => '/', 'node_type' => 'webpage'],
			['node_id' => 3, 'resource_name' => 'new.html', 'path' => '/', 'node_type' => 'webpage'],
		]);

		$diff = CmsSiteDiffService::diffSnapshots($baseline, $current);

		$this->assertSame(1, $diff['schema_version']);
		$this->assertSame('different', $diff['status']);
		$this->assertSame(1, $diff['summary']['rows_added']);
		$this->assertSame(1, $diff['summary']['rows_removed']);
		$this->assertSame(['resource:/old.html'], $diff['tables']['resource_tree']['missing_keys']);
		$this->assertSame(['resource:/new.html'], $diff['tables']['resource_tree']['extra_keys']);
		$this->assertArrayNotHasKey('created_at', $diff);
	}

	public function testDiffLiveBuildsInMemorySnapshotWithoutWritingOutputFiles(): void
	{
		$baseline = CmsSiteSnapshotService::exportSnapshot(true);
		$sentinel = DEPLOY_ROOT . 'tmp/site-diff-live-sentinel-' . bin2hex(random_bytes(4)) . '.json';
		$this->assertFileDoesNotExist($sentinel);

		$diff = CmsSiteDiffService::diffLive($baseline);

		$this->assertSame(1, $diff['schema_version']);
		$this->assertArrayHasKey('summary', $diff);
		$this->assertArrayNotHasKey('created_at', $diff);
		$this->assertFileDoesNotExist($sentinel);
	}

	/**
	 * @param list<array<string, mixed>> $resourceRows
	 * @return array<string, mixed>
	 */
	private function snapshot(array $resourceRows): array
	{
		return [
			'format' => CmsSiteSnapshotService::FORMAT,
			'version' => CmsSiteSnapshotService::VERSION,
			'schema' => [
				'tables' => [
					'resource_tree' => [
						'primary_keys' => ['node_id'],
					],
				],
			],
			'tables' => [
				'resource_tree' => $resourceRows,
			],
		];
	}
}
