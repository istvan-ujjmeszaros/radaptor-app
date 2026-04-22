<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class DbSchemaDataRuntimeTest extends TestCase
{
	public function testRuntimeSchemaOverrideIsScoped(): void
	{
		$baseline = DbSchemaData::getTableData('users');

		$this->assertIsArray($baseline);

		$staleTable = $this->buildStaleTableData($baseline);
		$cleanDsn = Db::redactDSNUserAndPassword(Db::normalizeDsn());

		$overridden = DbSchemaData::withRuntimeSchema(
			[
				$cleanDsn => [
					'users' => $staleTable,
				],
			],
			static fn (): ?array => DbSchemaData::getTableData('users')
		);

		$this->assertSame('__stale_runtime__', $overridden['field_names'][0]);
		$this->assertSame($baseline['field_names'], DbSchemaData::getTableData('users')['field_names']);
	}

	public function testBootstrapRefreshesRuntimeSchemaViewWhenExistingOverrideIsStale(): void
	{
		$baseline = DbSchemaData::getTableData('users');

		$this->assertIsArray($baseline);

		$cleanDsn = Db::redactDSNUserAndPassword(Db::normalizeDsn());
		$token = DbSchemaData::pushRuntimeSchema(
			[
				$cleanDsn => [
					'users' => $this->buildStaleTableData($baseline),
				],
			]
		);

		try {
			$this->assertSame('__stale_runtime__', DbSchemaData::getTableData('users')['field_names'][0]);

			$result = TestDatabaseSchemaSyncService::bootstrap();
			$refreshed = DbSchemaData::getTableData('users');

			$this->assertIsArray($refreshed);
			$this->assertTrue($result['runtime_schema_refreshed']);
			$this->assertSame($baseline['field_names'], $refreshed['field_names']);
			$this->assertNotSame('__stale_runtime__', $refreshed['field_names'][0]);
		} finally {
			DbSchemaData::popRuntimeSchema($token);
		}
	}

	/**
	 * @param array<string, mixed> $tableData
	 * @return array<string, mixed>
	 */
	private function buildStaleTableData(array $tableData): array
	{
		$stale = $tableData;
		$stale['field_names'] = ['__stale_runtime__'];
		$stale['pkeys'] = ['__stale_runtime__'];
		$stale['processable_fields'] = ['__stale_runtime__' => '__stale_runtime__'];
		$stale['fields'] = [[
			'column_name' => '__stale_runtime__',
			'type_sql' => 'varchar(255)',
			'type_php' => 'string',
			'comment' => '',
			'default' => null,
			'extra' => '',
			'is_optional' => true,
			'is_processable' => true,
			'is_primary_key' => true,
			'is_auto_increment' => false,
		]];

		return $stale;
	}
}
