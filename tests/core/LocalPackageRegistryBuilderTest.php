<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once DEPLOY_ROOT . 'bootstrap/bootstrap.package_locator.php';

final class LocalPackageRegistryBuilderTest extends TestCase
{
	private array $cleanup_directories = [];

	protected function tearDown(): void
	{
		foreach ($this->cleanup_directories as $directory) {
			radaptorAppBootstrapDeleteDirectory($directory);
		}

		$this->cleanup_directories = [];
	}

	public function testPublishPackageIncludesRegistryMetadataFileInArchive(): void
	{
		$package_root = $this->createTempDirectory('package');
		$registry_root = $this->createTempDirectory('registry');

		file_put_contents($package_root . '/.registry-package.json', json_encode([
			'package' => 'radaptor/core/framework',
			'type' => 'core',
			'id' => 'framework',
			'version' => '0.1.0',
		], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
		file_put_contents($package_root . '/bootstrap.php', '<?php');

		$result = LocalPackageRegistryBuilder::publishPackage(
			$registry_root,
			$package_root,
			[
				'package' => 'radaptor/core/framework',
				'type' => 'core',
				'id' => 'framework',
				'version' => '0.1.0',
				'dependencies' => [],
				'composer' => [
					'require' => [],
				],
				'assets' => [
					'public' => [],
				],
				'dist_exclude' => [],
			],
			[
				'.registry-package.json',
				'bootstrap.php',
			]
		);

		$zip = new ZipArchive();
		$this->assertTrue($zip->open($result['dist_path']) === true);
		$this->assertNotFalse($zip->locateName('.registry-package.json'));
		$this->assertNotFalse($zip->locateName('bootstrap.php'));
		$zip->close();
	}

	public function testPublishPackageRejectsOverwritingExistingVersion(): void
	{
		$package_root = $this->createTempDirectory('package');
		$registry_root = $this->createTempDirectory('registry');

		file_put_contents($package_root . '/.registry-package.json', json_encode([
			'package' => 'radaptor/core/framework',
			'type' => 'core',
			'id' => 'framework',
			'version' => '0.1.0',
		], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
		file_put_contents($package_root . '/bootstrap.php', '<?php');

		$metadata = [
			'package' => 'radaptor/core/framework',
			'type' => 'core',
			'id' => 'framework',
			'version' => '0.1.0',
			'dependencies' => [],
			'composer' => [
				'require' => [],
			],
			'assets' => [
				'public' => [],
			],
			'dist_exclude' => [],
		];
		$tracked_files = [
			'.registry-package.json',
			'bootstrap.php',
		];

		LocalPackageRegistryBuilder::publishPackage(
			$registry_root,
			$package_root,
			$metadata,
			$tracked_files
		);

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage("Package 'radaptor/core/framework' version '0.1.0' is already published.");

		LocalPackageRegistryBuilder::publishPackage(
			$registry_root,
			$package_root,
			$metadata,
			$tracked_files
		);
	}

	private function createTempDirectory(string $suffix): string
	{
		$directory = sys_get_temp_dir() . '/radaptor-local-registry-test-' . $suffix . '-' . bin2hex(random_bytes(6));
		mkdir($directory, 0o777, true);
		$this->cleanup_directories[] = $directory;

		return $directory;
	}
}
