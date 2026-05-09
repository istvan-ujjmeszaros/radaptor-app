<?php

declare(strict_types=1);

final class PackageInstallServiceShippedI18nTest extends TransactionedTestCase
{
	public function testShippedI18nAutoSyncIsIdempotentAfterRepair(): void
	{
		$locale = LocaleService::getDefaultLocale();
		$domain = 'import_export';
		$key = 'action.export_csv';
		$context = '';
		$pdo = Db::instance();

		$baseline = $this->runShippedI18nInvariantWithoutBuild();

		$this->assertSame('ok', $baseline['audit']['status']);
		$this->assertTranslationExists($pdo, $domain, $key, $context, $locale);

		$initial = $this->runShippedI18nInvariantWithoutBuild();

		$this->assertSame('ok', $initial['audit']['status']);
		$this->assertFalse($initial['sync_ran']);

		$stmt = $pdo->prepare(
			"DELETE FROM i18n_translations
			WHERE domain = ? AND `key` = ? AND context = ? AND locale = ?"
		);
		$stmt->execute([$domain, $key, $context, $locale]);

		$drift = I18nShippedDatabaseAuditService::audit(['locales' => [$locale]]);

		$this->assertSame('needs_sync', $drift['status']);
		$this->assertContains($locale, $drift['sync_locales']);

		$repair = $this->runShippedI18nInvariantWithoutBuild();

		$this->assertTrue($repair['sync_ran']);
		$this->assertSame('ok', $repair['audit']['status']);
		$this->assertTranslationExists($pdo, $domain, $key, $context, $locale);

		$second = $this->runShippedI18nInvariantWithoutBuild();

		$this->assertSame('ok', $second['audit']['status']);
		$this->assertFalse($second['sync_ran']);
	}

	/**
	 * @return array{
	 *     audit: array<string, mixed>,
	 *     sync_ran: bool,
	 *     sync: array<string, mixed>|null
	 * }
	 */
	private function runShippedI18nInvariantWithoutBuild(): array
	{
		$method = new ReflectionMethod(PackageInstallService::class, 'syncShippedI18nIfNeeded');

		return $method->invoke(null, false);
	}

	private function assertTranslationExists(PDO $pdo, string $domain, string $key, string $context, string $locale): void
	{
		$stmt = $pdo->prepare(
			"SELECT 1
			FROM i18n_translations
			WHERE domain = ? AND `key` = ? AND context = ? AND locale = ?
			LIMIT 1"
		);
		$stmt->execute([$domain, $key, $context, $locale]);

		$this->assertSame('1', (string) $stmt->fetchColumn());
	}
}
