<?php

use PHPUnit\Framework\TestCase;

require_once DEPLOY_ROOT . 'bootstrap/bootstrap.package_locator.php';

final class BootstrapPackageLocatorTest extends TestCase
{
	private array $_tempDirectories = [];

	protected function tearDown(): void
	{
		putenv('RADAPTOR_REGISTRY_URL');

		foreach ($this->_tempDirectories as $directory) {
			radaptorAppBootstrapDeleteDirectory($directory);
		}

		$this->_tempDirectories = [];
	}

	public function testRegistryUrlPrefersEnvironmentOverride(): void
	{
		$appRoot = $this->_createTempAppRoot();
		$this->_writeJson($appRoot . '/radaptor.json', [
			'registries' => [
				'default' => [
					'url' => radaptorAppBootstrapGetPlaceholderRegistryUrl(),
				],
			],
		]);

		putenv('RADAPTOR_REGISTRY_URL=file:///tmp/custom-registry.json');

		$this->assertSame(
			'file:///tmp/custom-registry.json',
			radaptorAppBootstrapResolveRegistryUrl($appRoot)
		);
	}

	public function testEnsureCliFrameworkAvailableFailsWithoutRealRegistryUrl(): void
	{
		$appRoot = $this->_createTempAppRoot();
		$this->_writeJson($appRoot . '/radaptor.json', [
			'registries' => [
				'default' => [
					'url' => radaptorAppBootstrapGetPlaceholderRegistryUrl(),
				],
			],
		]);
		$this->_writeFrameworkLockfile($appRoot, 'radaptor/core/framework', '0.1.0');

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('RADAPTOR_REGISTRY_URL');

		radaptorAppBootstrapEnsureCliFrameworkAvailable($appRoot);
	}

	public function testEnsureCliFrameworkAvailableBootstrapsLockedFrameworkPackage(): void
	{
		$appRoot = $this->_createTempAppRoot();
		$registryRoot = $this->_createTempDirectory('registry');
		$archiveDirectory = $registryRoot . '/packages/radaptor-core-framework/0.1.0';
		mkdir($archiveDirectory, 0777, true);

		$archivePath = $archiveDirectory . '/plugin.zip';
		$this->_createFrameworkArchive($archivePath, [
			'bootstrap.php' => '<?php echo "framework bootstrap";',
			'bootstrap.autoloader.php' => '<?php echo "autoload";',
			'.registry-package.json' => json_encode(['name' => 'radaptor/core/framework'], JSON_PRETTY_PRINT),
		]);
		$archiveSha = strtolower(hash_file('sha256', $archivePath));

		$this->_writeJson($registryRoot . '/registry.json', [
			'packages' => [
				'radaptor/core/framework' => [
					'versions' => [
						'0.1.0' => [
							'dist' => [
								'url' => 'packages/radaptor-core-framework/0.1.0/plugin.zip',
								'sha256' => $archiveSha,
							],
						],
					],
				],
			],
		]);

		$this->_writeJson($appRoot . '/radaptor.json', [
			'registries' => [
				'default' => [
					'url' => radaptorAppBootstrapGetPlaceholderRegistryUrl(),
				],
			],
		]);
		$this->_writeFrameworkLockfile($appRoot, 'radaptor/core/framework', '0.1.0');

		putenv('RADAPTOR_REGISTRY_URL=file://' . $registryRoot . '/registry.json');

		radaptorAppBootstrapEnsureCliFrameworkAvailable($appRoot);

		$frameworkRoot = $appRoot . '/packages/registry/core/framework';
		$this->assertDirectoryExists($frameworkRoot);
		$this->assertFileExists($frameworkRoot . '/bootstrap.php');
		$this->assertFileExists($frameworkRoot . '/bootstrap.autoloader.php');
		$this->assertSame(
			radaptorAppBootstrapNormalizePath($frameworkRoot),
			radaptorAppBootstrapResolveFrameworkRoot($appRoot)
		);
	}

	private function _createTempAppRoot(): string
	{
		$directory = $this->_createTempDirectory('app');
		mkdir($directory . '/packages/registry/core', 0777, true);
		mkdir($directory . '/packages/dev/core', 0777, true);

		return $directory;
	}

	private function _createTempDirectory(string $prefix): string
	{
		$directory = sys_get_temp_dir() . '/radaptor-bootstrap-test-' . $prefix . '-' . bin2hex(random_bytes(6));

		mkdir($directory, 0777, true);
		$this->_tempDirectories[] = $directory;

		return $directory;
	}

	private function _writeJson(string $path, array $data): void
	{
		$parent = dirname($path);

		if (!is_dir($parent)) {
			mkdir($parent, 0777, true);
		}

		file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
	}

	private function _writeFrameworkLockfile(string $appRoot, string $packageName, string $version): void
	{
		$this->_writeJson($appRoot . '/radaptor.lock.json', [
			'lockfile_version' => 1,
			'core' => [
				'framework' => [
					'type' => 'core',
					'id' => 'framework',
					'package' => $packageName,
					'source' => [
						'type' => 'registry',
						'registry' => 'default',
						'version' => '^' . $version,
					],
					'resolved' => [
						'type' => 'registry',
						'registry' => 'default',
						'version' => $version,
						'path' => 'packages/registry/core/framework',
					],
				],
			],
		]);
	}

	private function _createFrameworkArchive(string $archivePath, array $files): void
	{
		$zip = new ZipArchive();
		$openResult = $zip->open($archivePath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

		$this->assertTrue($openResult === true, 'Unable to create framework test archive');

		foreach ($files as $relativePath => $contents) {
			$zip->addFromString('radaptor-core-framework/' . ltrim($relativePath, '/'), $contents);
		}

		$zip->close();
	}
}
