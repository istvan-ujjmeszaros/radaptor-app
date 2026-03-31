<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class PackageConfigTest extends TestCase
{
	private static string $packageLockPath;
	private static bool $packageLockExisted = false;
	private static ?string $packageLockContent = null;

	/** @var string[] */
	private array $cleanup_files = [];
	/** @var string[] */
	private array $cleanup_directories = [];

	public static function setUpBeforeClass(): void
	{
		parent::setUpBeforeClass();
		self::$packageLockPath = PackageLockfile::getPath();
		self::$packageLockExisted = is_file(self::$packageLockPath);
		self::$packageLockContent = self::$packageLockExisted
			? (file_get_contents(self::$packageLockPath) ?: null)
			: null;
	}

	public static function tearDownAfterClass(): void
	{
		self::restorePackageLockfile();
		parent::tearDownAfterClass();
	}

	protected function tearDown(): void
	{
		foreach ($this->cleanup_files as $file) {
			if (is_file($file)) {
				unlink($file);
			}
		}

		foreach (array_reverse($this->cleanup_directories) as $directory) {
			$this->removeDirectoryIfExists($directory);
		}

		self::restorePackageLockfile();
		PackageConfig::reset();
		parent::tearDown();
	}

	public function testPackageConfigMergesDefaultsAndTypedOverrides(): void
	{
		$package_id = 'testpkgcfg';
		$package_root = $this->makePackageRoot();
		$default_path = $package_root . '/config/default.php';
		$override_path = PackageConfig::getAppOverridePath('plugin', $package_id);
		$local_override_path = PackageConfig::getAppOverridePath('plugin', $package_id, true);
		$this->cleanup_files[] = $override_path;
		$this->cleanup_files[] = $local_override_path;

		file_put_contents($default_path, <<<'PHP'
<?php
return [
	'dsn' => null,
	'flag' => 'default',
	'nested' => [
		'first' => 'default',
		'second' => 'default',
	],
];
PHP);
		$this->ensureDirectory(dirname($override_path));
		file_put_contents($override_path, <<<'PHP'
<?php
return [
	'flag' => 'override',
	'nested' => [
		'second' => 'override',
	],
];
PHP);
		file_put_contents($local_override_path, <<<'PHP'
<?php
return [
	'local_only' => true,
	'nested' => [
		'third' => 'local',
	],
];
PHP);

		$config = PackageConfig::load('plugin', $package_id, $package_root);

		$this->assertSame(null, $config['dsn']);
		$this->assertSame('override', $config['flag']);
		$this->assertSame(
			[
				'first' => 'default',
				'second' => 'override',
				'third' => 'local',
			],
			$config['nested']
		);
		$this->assertTrue((bool) PackageConfig::get('plugin', $package_id, 'local_only', false, $package_root));
		$this->assertSame('fallback', PackageConfig::get('plugin', $package_id, 'missing', 'fallback', $package_root));
	}

	public function testPackageConfigSupportsCoreAndThemeDefaults(): void
	{
		$core_root = $this->makePackageRoot();
		$theme_root = $this->makePackageRoot();
		file_put_contents($core_root . '/config/default.php', <<<'PHP'
<?php
return ['scope' => 'core', 'value' => 1];
PHP);
		file_put_contents($theme_root . '/config/default.php', <<<'PHP'
<?php
return ['scope' => 'theme', 'value' => 2];
PHP);

		$this->assertSame(
			['scope' => 'core', 'value' => 1],
			PackageConfig::load('core', 'framework', $core_root)
		);
		$this->assertSame(
			['scope' => 'theme', 'value' => 2],
			PackageConfig::load('theme', 'portal-admin', $theme_root)
		);
	}

	public function testPluginConfigHelperDelegatesToPackageConfig(): void
	{
		$plugin_id = 'testplugincfg';
		$plugin_root = $this->makePackageRoot();
		$override_path = PackageConfig::getAppOverridePath('plugin', $plugin_id);
		$this->cleanup_files[] = $override_path;
		file_put_contents($plugin_root . '/config/default.php', <<<'PHP'
<?php
return ['value' => 'default'];
PHP);
		$this->ensureDirectory(dirname($override_path));
		file_put_contents($override_path, <<<'PHP'
<?php
return ['value' => 'override'];
PHP);

		$this->assertSame(
			PackageConfig::load('plugin', $plugin_id, $plugin_root),
			PluginConfigHelper::load($plugin_id, $plugin_root)
		);
	}

	public function testPackageConfigRejectsNonArrayFiles(): void
	{
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Package config file must return an array');

		$package_root = $this->makePackageRoot();
		file_put_contents($package_root . '/config/default.php', <<<'PHP'
<?php
return 'invalid';
PHP);

		PackageConfig::load('plugin', 'badconfig', $package_root);
	}

	public function testPackageConfigDoesNotSilentlyIgnoreCorruptLockfile(): void
	{
		file_put_contents(self::$packageLockPath, "{\n\t\"lockfile_version\": 1,\n\t\"plugins\": {\n");
		PackageLockfile::reset(self::$packageLockPath);
		PackageConfig::reset();

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Unable to load package lockfile while resolving config base path');

		PackageConfig::load('plugin', 'audit');
	}

	private function makePackageRoot(): string
	{
		$root = sys_get_temp_dir() . '/radaptor-package-config-' . bin2hex(random_bytes(8));
		$this->ensureDirectory($root . '/config');
		$this->cleanup_directories[] = $root;

		return $root;
	}

	private function ensureDirectory(string $directory): void
	{
		if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
			$this->fail("Unable to create directory: {$directory}");
		}
	}

	private function removeDirectoryIfExists(string $directory): void
	{
		if (!is_dir($directory)) {
			return;
		}

		$items = scandir($directory);

		if ($items === false) {
			return;
		}

		foreach ($items as $item) {
			if ($item === '.' || $item === '..') {
				continue;
			}

			$path = $directory . '/' . $item;

			if (is_dir($path) && !is_link($path)) {
				$this->removeDirectoryIfExists($path);
			} elseif (file_exists($path)) {
				unlink($path);
			}
		}

		rmdir($directory);
	}

	private static function restorePackageLockfile(): void
	{
		if (self::$packageLockExisted) {
			file_put_contents(self::$packageLockPath, self::$packageLockContent ?? '');
		} elseif (is_file(self::$packageLockPath)) {
			unlink(self::$packageLockPath);
		}

		PackageLockfile::reset(self::$packageLockPath);
	}
}
