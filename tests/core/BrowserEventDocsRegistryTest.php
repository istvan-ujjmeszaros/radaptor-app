<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class BrowserEventDocsRegistryTest extends TestCase
{
	private static string $generatedDocsPath;
	private static bool $generatedDocsExisted = false;
	private static ?string $generatedDocsContent = null;

	public static function setUpBeforeClass(): void
	{
		parent::setUpBeforeClass();

		self::$generatedDocsPath = DEPLOY_ROOT . ApplicationConfig::GENERATED_BROWSER_EVENT_DOCS_FILE;
		self::$generatedDocsExisted = file_exists(self::$generatedDocsPath);
		self::$generatedDocsContent = self::$generatedDocsExisted
			? (file_get_contents(self::$generatedDocsPath) ?: null)
			: null;
	}

	public static function tearDownAfterClass(): void
	{
		if (self::$generatedDocsExisted) {
			if (self::$generatedDocsContent !== null) {
				file_put_contents(self::$generatedDocsPath, self::$generatedDocsContent);
			}
		} elseif (file_exists(self::$generatedDocsPath)) {
			unlink(self::$generatedDocsPath);
		}

		BrowserEventDocsRegistry::reset();

		parent::tearDownAfterClass();
	}

	public function testBuildEventDocsGeneratesCuratedRegistry(): void
	{
		CLICommandBuildAutoloader::create();
		CLICommandBuildTemplates::create();
		CLICommandBuildEventDocs::create();
		BrowserEventDocsRegistry::reset();

		$docs = BrowserEventDocsRegistry::getAllEvents();

		$this->assertArrayHasKey('resource:view', $docs);
		$this->assertArrayHasKey('user:logout', $docs);
		$this->assertArrayHasKey('cli_runner:execute', $docs);
		$this->assertSame('resource.view', $docs['resource:view']['route']['event_name']);
		$this->assertSame("Url::getUrl('user.logout')", $docs['user:logout']['invocation']['url_php']);
		$this->assertSame('Developer Tools', $docs['cli_runner:execute']['group']);
		$this->assertArrayNotHasKey('sql:schema-info', $docs);
	}

	public function testGroupedEventsUsesDeclaredDocGroups(): void
	{
		CLICommandBuildAutoloader::create();
		CLICommandBuildTemplates::create();
		CLICommandBuildEventDocs::create();
		BrowserEventDocsRegistry::reset();

		$grouped = BrowserEventDocsRegistry::getGroupedEvents();

		$this->assertArrayHasKey('Runtime', $grouped);
		$this->assertArrayHasKey('Editing', $grouped);
		$this->assertArrayHasKey('I18n', $grouped);
		$this->assertArrayHasKey('Admin AJAX', $grouped);
	}
}
