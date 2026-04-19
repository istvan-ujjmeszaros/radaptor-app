<?php

/**
 * TestDatabaseGuard - Schema comparison and sync for test database.
 *
 * This class ensures the test database schema matches the development database.
 * It's called during test bootstrap to automatically sync schemas when needed.
 */
class TestDatabaseGuard
{
	/**
	 * Asserts that we're connected to a test database (name ends with _test).
	 * This is a safety check to prevent accidental operations on production.
	 *
	 * @throws RuntimeException If not connected to a test database
	 */
	public static function assertTestDatabase(): void
	{
		$testDsn = Db::normalizeDsn();
		$dbName = Db::getDatabasenameFromDsn($testDsn);

		if (!str_ends_with($dbName, '_test')) {
			throw new RuntimeException(
				"Safety check failed: Expected test database (ending with _test), got '{$dbName}'. "
				. "Ensure ENVIRONMENT=test is set."
			);
		}
	}

	/**
	 * Compares the test database schema to the development database schema.
	 *
	 * @return array<string, array<string>> Array of errors grouped by type (missing_tables, extra_tables, column_mismatches)
	 */
	public static function getSchemaErrors(): array
	{
		$devDbName = self::extractDbNameFromDsn(Config::DB_DEFAULT_DSN->value());
		$testDbName = $devDbName . '_test';
		$devAuditDbName = $devDbName . '_audit';
		$testAuditDbName = $devDbName . '_test_audit';

		$errors = [];

		self::collectSchemaErrors(
			$errors,
			self::getDevPdo(),
			$devDbName,
			Db::instance(),
			$testDbName
		);

		self::collectSchemaErrors(
			$errors,
			self::getDevAuditPdo(),
			$devAuditDbName,
			Db::instance(Db::rewriteDsnToAudit('')),
			$testAuditDbName,
			'audit_'
		);

		return array_filter($errors, fn ($arr) => !empty($arr));
	}

	/**
	 * Recreates the test database schema from the development database.
	 * Drops all test tables and copies structure from dev.
	 */
	public static function recreateSchema(): void
	{
		$devDbName = self::extractDbNameFromDsn(Config::DB_DEFAULT_DSN->value());
		$testDbName = $devDbName . '_test';
		$devAuditDbName = $devDbName . '_audit';
		$testAuditDbName = $devDbName . '_test_audit';

		self::recreateDatabaseSchema(
			self::getDevPdo(),
			$devDbName,
			Db::instance(),
			$testDbName
		);

		self::recreateDatabaseSchema(
			self::getDevAuditPdo(),
			$devAuditDbName,
			Db::instance(Db::rewriteDsnToAudit('')),
			$testAuditDbName
		);
	}

	/**
	 * Truncates all tables in the test database for clean fixture loading.
	 */
	public static function truncateAllTables(): void
	{
		$testPdo = Db::instance();
		$testDbName = Db::getDatabasenameFromDsn(Db::normalizeDsn());

		// Disable foreign key checks
		$testPdo->exec('SET FOREIGN_KEY_CHECKS = 0');

		try {
			$tables = self::getTables($testPdo, $testDbName);

			foreach ($tables as $table) {
				$testPdo->exec("TRUNCATE TABLE `{$table}`");
			}
		} finally {
			// Re-enable foreign key checks
			$testPdo->exec('SET FOREIGN_KEY_CHECKS = 1');
		}
	}

	/**
	 * Gets a PDO connection to the development database (bypasses _test suffix rewriting).
	 *
	 * @return PDO Connection to dev database
	 */
	private static function getDevPdo(): PDO
	{
		static $devPdo = null;

		if ($devPdo === null) {
			$devDsn = Config::DB_DEFAULT_DSN->value();
			$devPdo = new PDO($devDsn);
			$devPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$devPdo->exec('SET NAMES utf8mb4');
		}

		return $devPdo;
	}

	/**
	 * Gets a PDO connection to the development audit database.
	 *
	 * @return PDO Connection to dev audit database
	 */
	private static function getDevAuditPdo(): PDO
	{
		static $devAuditPdo = null;

		if ($devAuditPdo === null) {
			$devAuditDsn = self::rewriteRawDsnToAudit(Config::DB_DEFAULT_DSN->value());
			$devAuditPdo = new PDO($devAuditDsn);
			$devAuditPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$devAuditPdo->exec('SET NAMES utf8mb4');
		}

		return $devAuditPdo;
	}

	/**
	 * Gets list of tables in a database.
	 *
	 * @param PDO $pdo Database connection
	 * @param string $dbName Database name
	 * @return list<string> Table names
	 */
	private static function getTables(PDO $pdo, string $dbName): array
	{
		$stmt = $pdo->prepare(
			"SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_TYPE = 'BASE TABLE'"
		);
		$stmt->execute([$dbName]);

		return $stmt->fetchAll(PDO::FETCH_COLUMN);
	}

	/**
	 * Compare a development and test database schema and append mismatches.
	 *
	 * @param array<string, array<string>> $errors
	 */
	private static function collectSchemaErrors(
		array &$errors,
		PDO $devPdo,
		string $devDbName,
		PDO $testPdo,
		string $testDbName,
		string $prefix = ''
	): void {
		$errors[$prefix . 'missing_tables'] = [];
		$errors[$prefix . 'extra_tables'] = [];
		$errors[$prefix . 'column_mismatches'] = [];

		$devTables = self::getTables($devPdo, $devDbName);
		$testTables = self::getTables($testPdo, $testDbName);

		$errors[$prefix . 'missing_tables'] = array_diff($devTables, $testTables);
		$errors[$prefix . 'extra_tables'] = array_diff($testTables, $devTables);

		$commonTables = array_intersect($devTables, $testTables);

		foreach ($commonTables as $table) {
			$devCreate = self::getCreateTable($devPdo, $table);
			$testCreate = self::getCreateTable($testPdo, $table);

			$devNormalized = self::normalizeCreateTable($devCreate);
			$testNormalized = self::normalizeCreateTable($testCreate);

			if ($devNormalized !== $testNormalized) {
				$errors[$prefix . 'column_mismatches'][] = $table;
			}
		}
	}

	/**
	 * Recreate a test database from its development counterpart.
	 */
	private static function recreateDatabaseSchema(
		PDO $devPdo,
		string $devDbName,
		PDO $testPdo,
		string $testDbName
	): void {
		$testPdo->exec('SET FOREIGN_KEY_CHECKS = 0');

		try {
			$testTables = self::getTables($testPdo, $testDbName);

			foreach ($testTables as $table) {
				$testPdo->exec("DROP TABLE IF EXISTS `{$table}`");
			}

			$devTables = self::getTables($devPdo, $devDbName);

			foreach ($devTables as $table) {
				$createStmt = self::getCreateTable($devPdo, $table);
				$testPdo->exec($createStmt);
			}
		} finally {
			$testPdo->exec('SET FOREIGN_KEY_CHECKS = 1');
		}
	}

	/**
	 * Gets the CREATE TABLE statement for a table.
	 *
	 * @param PDO $pdo Database connection
	 * @param string $table Table name
	 * @return string CREATE TABLE statement
	 */
	private static function getCreateTable(PDO $pdo, string $table): string
	{
		$stmt = $pdo->query("SHOW CREATE TABLE `{$table}`");
		$row = $stmt->fetch(PDO::FETCH_ASSOC);

		return $row['Create Table'] ?? '';
	}

	/**
	 * Normalizes a CREATE TABLE statement for comparison.
	 * Removes AUTO_INCREMENT values which may differ between databases.
	 *
	 * @param string $createStmt CREATE TABLE statement
	 * @return string Normalized statement
	 */
	private static function normalizeCreateTable(string $createStmt): string
	{
		// Remove AUTO_INCREMENT=N from table options
		$normalized = preg_replace('/AUTO_INCREMENT=\d+\s*/i', '', $createStmt);

		// Normalize whitespace
		$normalized = preg_replace('/\s+/', ' ', (string) $normalized);

		return trim((string) $normalized);
	}

	/**
	 * Extracts the database name from a DSN string without normalization.
	 * This bypasses the Db::getDatabasenameFromDsn which calls normalizeDsn
	 * and would rewrite to test database in testing environment.
	 *
	 * @param string $dsn The DSN string
	 * @return string The database name
	 */
	private static function extractDbNameFromDsn(string $dsn): string
	{
		$parts = explode(';', $dsn);

		foreach ($parts as $part) {
			if (str_starts_with($part, 'dbname=')) {
				return substr($part, strlen('dbname='));
			}
		}

		throw new RuntimeException("DSN does not contain a database name: {$dsn}");
	}

	/**
	 * Rewrites a raw DSN to point to the audit database without test-environment normalization.
	 */
	private static function rewriteRawDsnToAudit(string $dsn): string
	{
		$parts = explode(';', $dsn);
		$dsnComponents = [];

		foreach ($parts as $part) {
			if (str_starts_with($part, 'dbname=')) {
				$dbName = substr($part, strlen('dbname='));

				if (!str_ends_with($dbName, '_audit')) {
					$part = 'dbname=' . $dbName . '_audit';
				}
			}

			$dsnComponents[] = $part;
		}

		return implode(';', $dsnComponents);
	}
}
