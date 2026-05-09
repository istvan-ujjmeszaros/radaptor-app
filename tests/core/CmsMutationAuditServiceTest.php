<?php

declare(strict_types=1);

final class CmsMutationAuditServiceTest extends TransactionedTestCase
{
	private string $operationPrefix;

	#[\Override]
	protected function setUp(): void
	{
		parent::setUp();
		$this->operationPrefix = 'test.audit.' . bin2hex(random_bytes(4));
		$this->deleteAuditRows();
		CmsMutationAuditService::resetForTests();
	}

	#[\Override]
	protected function tearDown(): void
	{
		$this->deleteAuditRows();
		CmsMutationAuditService::resetForTests();
		parent::tearDown();
	}

	public function testContextAuditRedactsSensitiveAndLongArguments(): void
	{
		CmsMutationAuditService::withContext(
			$this->operationPrefix . '.redaction',
			[
				'password' => 'super-secret',
				'passwd' => 'also-secret',
				'session_id' => 'session-secret',
				'spec_json' => str_repeat('x', 512),
			],
			static fn (): bool => true
		);

		$rows = $this->fetchAuditRows();
		$this->assertCount(2, $rows);
		$this->assertSame('context_started', $rows[0]['phase']);
		$this->assertSame('context_finished', $rows[1]['phase']);
		$this->assertSame('success', $rows[1]['result_status']);
		$this->assertNotSame('', (string) $rows[0]['args_hash']);

		$args = json_decode((string) $rows[0]['args_redacted_json'], true, 512, JSON_THROW_ON_ERROR);
		$this->assertSame('[redacted]', $args['password'] ?? null);
		$this->assertSame('[redacted]', $args['passwd'] ?? null);
		$this->assertSame('[redacted]', $args['session_id'] ?? null);
		$this->assertSame(true, $args['spec_json']['__redacted_long_scalar'] ?? null);
		$this->assertSame(512, $args['spec_json']['__length'] ?? null);
	}

	public function testFailedContextWritesFailureAuditRow(): void
	{
		try {
			CmsMutationAuditService::withContext(
				$this->operationPrefix . '.failed',
				[],
				static function (): void {
					throw new RuntimeException('planned rollback failure');
				}
			);
			$this->fail('Expected exception was not thrown.');
		} catch (RuntimeException $exception) {
			$this->assertSame('planned rollback failure', $exception->getMessage());
		}

		$rows = $this->fetchAuditRows();
		$this->assertCount(2, $rows);
		$this->assertSame('failed', $rows[1]['result_status']);
		$this->assertSame(RuntimeException::class, $rows[1]['error_class']);
	}

	public function testNestedAuditContextKeepsCorrelationAndParentLinks(): void
	{
		CmsMutationAuditService::withContext(
			$this->operationPrefix . '.outer',
			[],
			function (): void {
				CmsMutationAuditService::recordLeaf($this->operationPrefix . '.outer.leaf', [
					'affected_count' => 3,
				]);

				CmsMutationAuditService::withContext(
					$this->operationPrefix . '.inner',
					[],
					static fn (): bool => true
				);
			}
		);

		$outer_rows = $this->fetchAuditRowsForOperation($this->operationPrefix . '.outer');
		$outer_started = $this->firstRowByPhase($outer_rows, 'context_started');
		$outer_leaf_rows = $this->fetchAuditRowsForOperation($this->operationPrefix . '.outer.leaf');
		$this->assertCount(1, $outer_leaf_rows);
		$outer_leaf = $outer_leaf_rows[0];
		$this->assertSame($outer_started['correlation_id'], $outer_leaf['correlation_id']);
		$this->assertSame(3, (int) $outer_leaf['affected_count']);

		$inner_rows = $this->fetchAuditRowsForOperation($this->operationPrefix . '.inner');
		$inner_started = $this->firstRowByPhase($inner_rows, 'context_started');
		$this->assertSame($outer_started['correlation_id'], $inner_started['parent_correlation_id']);
		$this->assertNotSame($outer_started['correlation_id'], $inner_started['correlation_id']);
	}

	public function testImplicitAuditContextUsesInternalActorAndNullCliCommand(): void
	{
		CmsMutationAuditService::recordLeaf($this->operationPrefix . '.implicit', [
			'affected_count' => 1,
		]);

		$rows = $this->fetchAuditRowsForOperation($this->operationPrefix . '.implicit');
		$this->assertCount(3, $rows);
		$this->assertSame('context_started', $rows[0]['phase']);
		$this->assertSame('leaf', $rows[1]['phase']);
		$this->assertSame('context_finished', $rows[2]['phase']);
		$this->assertSame('internal', $rows[0]['actor_type']);
		$this->assertNull($rows[0]['cli_command']);
		$this->assertSame($rows[0]['correlation_id'], $rows[1]['correlation_id']);
		$this->assertSame(1, (int) $rows[1]['affected_count']);
	}

	public function testAuditPayloadsOver64KilobytesAreTruncated(): void
	{
		CmsMutationAuditService::withContext(
			$this->operationPrefix . '.truncate',
			[],
			function (): void {
				CmsMutationAuditService::recordLeaf($this->operationPrefix . '.truncate.leaf', [
					'before' => [
						'blob' => str_repeat('x', 70000),
					],
				]);
			}
		);

		$rows = $this->fetchAuditRowsForOperation($this->operationPrefix . '.truncate.leaf');
		$this->assertCount(1, $rows);

		$before = json_decode((string) $rows[0]['before_json'], true, 512, JSON_THROW_ON_ERROR);
		$this->assertSame(true, $before['__truncated'] ?? null);
		$this->assertGreaterThan(65536, (int) ($before['__original_size'] ?? 0));
		$this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', (string) ($before['__sha256'] ?? ''));
	}

	public function testAuditPruneDryRunReportsWithoutDeletingAndApplyDeletesOldRows(): void
	{
		CmsMutationAuditService::withContext(
			$this->operationPrefix . '.prune',
			[],
			static fn (): bool => true
		);
		$this->backdateAuditRows($this->operationPrefix . '.prune', '2001-01-01 00:00:00');

		$dry_run = CmsMutationAuditService::prune(1, true);
		$this->assertSame('success', $dry_run['status']);
		$this->assertTrue($dry_run['dry_run']);
		$this->assertGreaterThanOrEqual(2, (int) $dry_run['matched_rows']);
		$this->assertSame(0, (int) $dry_run['deleted_rows']);
		$this->assertCount(2, $this->fetchAuditRowsForOperation($this->operationPrefix . '.prune'));

		$apply = CmsMutationAuditService::prune(1, false);
		$this->assertSame('success', $apply['status']);
		$this->assertFalse($apply['dry_run']);
		$this->assertGreaterThanOrEqual(2, (int) $apply['deleted_rows']);
		$this->assertSame([], $this->fetchAuditRowsForOperation($this->operationPrefix . '.prune'));
	}

	/**
	 * @return list<array<string, mixed>>
	 */
	private function fetchAuditRows(): array
	{
		$stmt = Db::createIndependentPdoConnection()->prepare(
			'SELECT * FROM `cms_mutation_audit` WHERE `operation` LIKE ? ORDER BY `cms_mutation_audit_id` ASC'
		);
		$stmt->execute([$this->operationPrefix . '%']);

		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	/**
	 * @return list<array<string, mixed>>
	 */
	private function fetchAuditRowsForOperation(string $operation): array
	{
		$stmt = Db::createIndependentPdoConnection()->prepare(
			'SELECT * FROM `cms_mutation_audit` WHERE `operation` = ? ORDER BY `cms_mutation_audit_id` ASC'
		);
		$stmt->execute([$operation]);

		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	/**
	 * @param list<array<string, mixed>> $rows
	 * @return array<string, mixed>
	 */
	private function firstRowByPhase(array $rows, string $phase): array
	{
		foreach ($rows as $row) {
			if (($row['phase'] ?? null) === $phase) {
				return $row;
			}
		}

		$this->fail("Audit phase {$phase} was not found.");
	}

	private function backdateAuditRows(string $operation, string $created_at): void
	{
		$stmt = Db::createIndependentPdoConnection()->prepare(
			'UPDATE `cms_mutation_audit` SET `created_at` = ? WHERE `operation` = ?'
		);
		$stmt->execute([$created_at, $operation]);
	}

	private function deleteAuditRows(): void
	{
		try {
			$pdo = Db::createIndependentPdoConnection();
			$stmt = $pdo->prepare('DELETE FROM `cms_mutation_audit` WHERE `operation` LIKE ?');
			$stmt->execute([$this->operationPrefix . '%']);
		} catch (Throwable) {
		}
	}
}
