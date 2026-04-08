<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class BuildDbPluginHookTest extends TestCase
{
	private static string $generatedDbPath;
	private static bool $generatedDbExisted = false;
	private static ?string $generatedDbContent = null;

	public static function setUpBeforeClass(): void
	{
		parent::setUpBeforeClass();
		self::$generatedDbPath = DEPLOY_ROOT . ApplicationConfig::GENERATED_DB_FILE;
		self::$generatedDbExisted = file_exists(self::$generatedDbPath);
		self::$generatedDbContent = self::$generatedDbExisted
			? (file_get_contents(self::$generatedDbPath) ?: null)
			: null;
	}

	public static function tearDownAfterClass(): void
	{
		if (self::$generatedDbExisted) {
			if (self::$generatedDbContent !== null) {
				file_put_contents(self::$generatedDbPath, self::$generatedDbContent);
			}
		} elseif (file_exists(self::$generatedDbPath)) {
			unlink(self::$generatedDbPath);
		}

		parent::tearDownAfterClass();
	}

	public function testBuildDbRebuildsAuditTriggersThroughPluginHook(): void
	{
		$dsn = Config::DB_DEFAULT_DSN->value();
		AuditTriggerManager::deleteForDsn($dsn);
		$this->assertSame(0, $this->countAuditTriggers($dsn));
		CLICommandBuildDb::create([$dsn]);

		$this->assertGreaterThan(0, $this->countAuditTriggers($dsn));
	}

	private function countAuditTriggers(string $dsn): int
	{
		$stmt = Db::instance($dsn)->prepare(
			"SHOW TRIGGERS WHERE `Trigger` LIKE '%_BI' OR `Trigger` LIKE '%_BU' OR `Trigger` LIKE '%_BD' OR `Trigger` LIKE '%_AU'"
		);
		$stmt->execute();

		return count($stmt->fetchAll(PDO::FETCH_ASSOC));
	}
}
