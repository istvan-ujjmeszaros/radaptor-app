<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class MigrationContentGuardRuntimeTest extends TestCase
{
	private const int TEST_USER_ID = 424243;

	private ?int $resource_id = null;
	private ?string $migration_path = null;

	protected function setUp(): void
	{
		User::logout();
		User::bootstrapTrustedCurrentUser([
			'user_id' => self::TEST_USER_ID,
			'username' => 'migration-content-guard-test',
		]);
	}

	protected function tearDown(): void
	{
		try {
			if (is_int($this->resource_id) && ResourceTreeHandler::getResourceTreeEntryDataById($this->resource_id) !== null) {
				ResourceTreeHandler::deleteResourceEntry($this->resource_id);
			}
		} finally {
			if (is_string($this->migration_path) && $this->migration_path !== '') {
				@unlink($this->migration_path);
			}

			User::logout();
		}
	}

	public function testMigrationContentGuardRollsBackRejectedResourceTreeDeletion(): void
	{
		$root = CmsPathHelper::resolveFolder('/');
		$this->assertIsArray($root);

		$resource_name = 'migration-guard-rollback-' . bin2hex(random_bytes(4));
		$this->resource_id = ResourceTreeHandler::addResourceEntry([
			'node_type' => 'folder',
			'resource_name' => $resource_name,
		], (int) $root['node_id']);
		$this->assertIsInt($this->resource_id);

		$suffix = 'guard_rollback_' . bin2hex(random_bytes(4));
		$filename = '20260504_235959_' . $suffix . '.php';
		$class_name = 'Migration_20260504_235959_' . $suffix;
		$this->migration_path = sys_get_temp_dir() . '/' . $filename;
		$source = "<?php\n"
			. "class {$class_name}\n"
			. "{\n"
			. "\tpublic function run(): void\n"
			. "\t{\n"
			. "\t\t\$table = 'resource_' . 'tree';\n"
			. "\t\tDb::instance()->prepare(\"DELETE FROM {\$table} WHERE node_id = ?\")->execute([{$this->resource_id}]);\n"
			. "\t}\n"
			. "}\n";
		file_put_contents($this->migration_path, $source);

		$result = MigrationRunner::runMigration($this->migration_path, 'app');
		$this->assertFalse($result['success']);
		$this->assertStringContainsString('deleted CMS resource_tree rows', $result['message']);

		Cache::flush();
		$this->assertIsArray(ResourceTreeHandler::getResourceTreeEntryDataById($this->resource_id));
	}
}
