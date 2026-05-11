<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ResourceTreeArchitectureTest extends TestCase
{
	public function testResourceTreeNestedSetCallsStayInsideResourceTreeHandler(): void
	{
		$violations = [];
		$roots = self::getInspectableCoreRoots();

		$this->assertNotSame([], $roots, 'ResourceTree architecture test must inspect at least one installed or active core package root.');

		foreach ($roots as $root) {
			$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));

			foreach ($iterator as $file) {
				if (!$file->isFile() || $file->getExtension() !== 'php') {
					continue;
				}

				$path = $file->getPathname();

				if (str_ends_with((string) $path, 'modules-common/Cms/classes/class.ResourceTreeHandler.php')) {
					continue;
				}

				if (str_contains((string) $path, '/tests/')) {
					continue;
				}

				$contents = file_get_contents($path);

				if (!is_string($contents)) {
					continue;
				}

				if (preg_match('/NestedSet::[A-Za-z0-9_]+\(\s*[\'"]resource_tree[\'"]/', $contents) === 1) {
					$violations[] = str_replace(DEPLOY_ROOT, '', $path);
				}
			}
		}

		$this->assertSame([], $violations, 'Direct NestedSet resource_tree access must stay inside ResourceTreeHandler.');
	}

	public function testNestedSetStructuralSqlStaysInsideNestedSetPrimitive(): void
	{
		$violations = [];
		$roots = self::getInspectableCoreRoots();
		$table_pattern = self::getNestedSetTableRegex();

		$this->assertNotSame([], $roots, 'Nested-set architecture test must inspect at least one installed or active core package root.');

		foreach ($roots as $root) {
			$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));

			foreach ($iterator as $file) {
				if (!$file->isFile() || $file->getExtension() !== 'php') {
					continue;
				}

				$path = $file->getPathname();

				if (str_ends_with((string) $path, 'classes/class.NestedSet.php')) {
					continue;
				}

				$contents = file_get_contents($path);

				if (!is_string($contents)) {
					continue;
				}

				$raw_structural_sql = '/\b(?:INSERT\s+INTO|DELETE\s+FROM)\s+`?' . $table_pattern . '`?\b|\bUPDATE\s+`?' . $table_pattern . '`?\s+SET\b(?:(?!\bWHERE\b).)*(?:\blft\b|\brgt\b|\bparent_id\b)/is';

				if (preg_match($raw_structural_sql, $contents) === 1) {
					$violations[] = str_replace(DEPLOY_ROOT, '', $path);
				}
			}
		}

		$this->assertSame([], $violations, 'Nested-set structural SQL must stay inside NestedSet.');
	}

	public function testCorePackageMigrationsDoNotDeleteResourceTreeContent(): void
	{
		$violations = [];
		$patterns = [
			'/\bDELETE\s+FROM\s+`?resource_tree`?\b/i',
			'/\bTRUNCATE\s+(?:TABLE\s+)?`?resource_tree`?\b/i',
			'/\bDROP\s+TABLE\s+(?:IF\s+EXISTS\s+)?`?resource_tree`?\b/i',
			'/ResourceTreeHandler::deleteResourceEntr(?:y|ies)Recursive\s*\(/',
			'/ResourceTreeHandler::deleteResourceEntry\s*\(/',
			'/NestedSet::deleteNode\s*\(\s*[\'"]resource_tree[\'"]/',
		];

		foreach (self::getInspectableCoreRoots() as $root) {
			$migration_files = [
				...(glob($root . '/migrations/*.php') ?: []),
				...(glob($root . '/framework/migrations/*.php') ?: []),
			];

			foreach ($migration_files as $path) {
				$contents = file_get_contents($path);

				if (!is_string($contents)) {
					continue;
				}

				foreach ($patterns as $pattern) {
					if (preg_match($pattern, $contents) === 1) {
						$violations[] = str_replace(DEPLOY_ROOT, '', $path);

						break;
					}
				}
			}
		}

		$this->assertSame([], array_values(array_unique($violations)), 'Package migrations must never delete resource_tree content.');
	}

	public function testMigrationContentGuardRejectsResourceTreeDeletionSource(): void
	{
		$path = sys_get_temp_dir() . '/radaptor_bad_migration_' . bin2hex(random_bytes(4)) . '.php';
		file_put_contents($path, "<?php\nResourceTreeHandler::deleteResourceEntry(123);\n");

		try {
			$this->expectException(RuntimeException::class);
			$this->expectExceptionMessage('not allowed to delete CMS resources');

			MigrationContentGuard::assertMigrationSourceAllowed($path);
		} finally {
			@unlink($path);
		}
	}

	private static function getNestedSetTableRegex(): string
	{
		$tables = Db::getNestedSetTables();

		self::assertNotSame([], $tables, 'Nested-set architecture test needs at least one nested-set table in schema.');

		return '(' . implode('|', array_map(
			static fn (string $table): string => preg_quote($table, '/'),
			$tables
		)) . ')';
	}

	/**
	 * @return list<string>
	 */
	private static function getInspectableCoreRoots(): array
	{
		$roots = array_values(array_filter(
			PackagePathHelper::getActivePackageRoots(['core']),
			static fn (string $root): bool => is_dir($root)
		));

		if ($roots !== []) {
			return $roots;
		}

		return array_values(array_filter(
			[DEPLOY_ROOT . 'packages/registry/core'],
			static fn (string $root): bool => is_dir($root)
		));
	}
}
