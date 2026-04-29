<?php

declare(strict_types=1);

final class NestedSetGuardTest extends TransactionedTestCase
{
	public function testNestedSetTableDetectionUsesSchemaFields(): void
	{
		$this->assertTrue(Db::isNestedSetTable('resource_tree'));
		$this->assertFalse(Db::isNestedSetTable('users'));
	}

	public function testNestedSetTreeResolutionAcceptsShortKeyAndTableName(): void
	{
		$this->assertSame('resource_tree', NestedSet::resolveTreeTable('resource'));
		$this->assertSame('resource_tree', NestedSet::resolveTreeTable('resource_tree'));
		$this->assertNull(NestedSet::resolveTreeTable('nonexistent'));
	}

	public function testDbHelperRejectsNestedSetStructuralUpdate(): void
	{
		[$table, $row] = $this->getNestedSetTableWithRows();

		$this->expectException(LogicException::class);

		DbHelper::updateHelper($table, [
			'node_id' => (int) $row['node_id'],
			'lft' => (int) $row['lft'],
		]);
	}

	public function testDbHelperRejectsNestedSetRowInsert(): void
	{
		[$table] = $this->getNestedSetTableWithRows();

		$this->expectException(LogicException::class);

		DbHelper::insertHelper($table, []);
	}

	public function testResourceTreeHandlerRejectsStructuralUpdate(): void
	{
		$pages = DbHelper::selectMany('resource_tree', ['node_type' => 'webpage'], 1, 'node_id ASC');
		$this->assertNotSame([], $pages);
		$page = $pages[0];

		$this->expectException(InvalidArgumentException::class);

		ResourceTreeHandler::updateResourceTreeEntry([
			'parent_id' => (int) $page['parent_id'],
		], (int) $page['node_id']);
	}

	/**
	 * @return array{0: string, 1: array<string, mixed>}
	 */
	private function getNestedSetTableWithRows(): array
	{
		foreach (Db::getNestedSetTables() as $table) {
			$rows = DbHelper::selectMany($table, [], 1, 'node_id ASC');

			if ($rows !== []) {
				return [$table, $rows[0]];
			}
		}

		$this->fail('At least one nested-set table must contain rows in fixtures.');
	}
}
