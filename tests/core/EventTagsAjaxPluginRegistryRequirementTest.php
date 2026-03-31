<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class EventTagsAjaxPluginRegistryRequirementTest extends TestCase
{
	private static string $generatedPluginsPath;
	private static bool $generatedPluginsExisted = false;
	private static ?string $generatedPluginsContent = null;

	public static function setUpBeforeClass(): void
	{
		parent::setUpBeforeClass();

		self::$generatedPluginsPath = DEPLOY_ROOT . ApplicationConfig::GENERATED_PLUGINS_FILE;
		self::$generatedPluginsExisted = file_exists(self::$generatedPluginsPath);
		self::$generatedPluginsContent = self::$generatedPluginsExisted
			? (file_get_contents(self::$generatedPluginsPath) ?: null)
			: null;
	}

	public static function tearDownAfterClass(): void
	{
		if (self::$generatedPluginsExisted) {
			if (self::$generatedPluginsContent !== null) {
				file_put_contents(self::$generatedPluginsPath, self::$generatedPluginsContent);
			}
		} elseif (file_exists(self::$generatedPluginsPath)) {
			unlink(self::$generatedPluginsPath);
		}

		PluginRegistry::reset();
		PluginRegistry::clearGeneratedPluginsCache();

		parent::tearDownAfterClass();
	}

	protected function tearDown(): void
	{
		if (self::$generatedPluginsExisted) {
			if (self::$generatedPluginsContent !== null) {
				file_put_contents(self::$generatedPluginsPath, self::$generatedPluginsContent);
			}
		} elseif (file_exists(self::$generatedPluginsPath)) {
			unlink(self::$generatedPluginsPath);
		}

		PluginRegistry::reset();
		PluginRegistry::clearGeneratedPluginsCache();

		parent::tearDown();
	}

	public function testRunRequiresGeneratedPluginRegistryAtRuntime(): void
	{
		$this->assertFileExists(self::$generatedPluginsPath);
		unlink(self::$generatedPluginsPath);

		RequestContextHolder::initializeRequest([
			'term' => 'bug',
			'tag_context' => 'tracker_ticket',
		]);

		PluginRegistry::reset();
		PluginRegistry::clearGeneratedPluginsCache();

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Generated plugin registry is missing.');

		(new EventTagsAjax())->run();
	}
}
