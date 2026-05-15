<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class PackageStateInspectorTest extends TestCase
{
	/** @var string[] */
	private array $cleanupDirectories = [];

	/** @var array<string, false|string> */
	private array $originalEnv = [];

	protected function setUp(): void
	{
		parent::setUp();
		$this->originalEnv = [
			'RADAPTOR_PACKAGE_REGISTRY_ROOT' => getenv('RADAPTOR_PACKAGE_REGISTRY_ROOT'),
			'RADAPTOR_WORKSPACE_DEV_MODE' => getenv('RADAPTOR_WORKSPACE_DEV_MODE'),
			'RADAPTOR_DEV_ROOT' => getenv('RADAPTOR_DEV_ROOT'),
			'RADAPTOR_DISABLE_LOCAL_OVERRIDES' => getenv('RADAPTOR_DISABLE_LOCAL_OVERRIDES'),
		];

		putenv('RADAPTOR_DISABLE_LOCAL_OVERRIDES');
	}

	protected function tearDown(): void
	{
		foreach ($this->originalEnv as $key => $value) {
			if ($value === false) {
				putenv($key);

				continue;
			}

			putenv($key . '=' . $value);
		}

		PackageLocalOverrideHelper::reset();

		foreach (array_reverse($this->cleanupDirectories) as $directory) {
			$this->removeDirectory($directory);
		}

		$this->originalEnv = [];
		parent::tearDown();
	}

	public function testStatusReportsRegistryFirstMode(): void
	{
		$appRoot = $this->makeScratchAppRoot();

		putenv('RADAPTOR_DISABLE_LOCAL_OVERRIDES=1');
		putenv('RADAPTOR_PACKAGE_REGISTRY_ROOT=' . $this->makeRegistryRoot([
			'radaptor/core/framework' => '0.1.13',
			'radaptor/core/cms' => '0.1.6',
		]));

		$status = PackageStateInspector::getStatusForAppRoot($appRoot);
		$framework = $this->findPackage($status['packages'], 'core:framework');

		$this->assertSame('registry-first', $status['mode']);
		$this->assertSame($appRoot, $status['app_root']);
		$this->assertSame($appRoot . '/radaptor.json', $status['manifest_path']);
		$this->assertSame($appRoot . '/radaptor.lock.json', $status['lock_path']);
		$this->assertSame('registry', $framework['source_type']);
		$this->assertNull($framework['source_commit']);
		$this->assertNull($framework['source_dirty']);
		$this->assertSame('up-to-date', $framework['freshness']);
	}

	public function testStatusReportsWorkspaceDevModeForLocalFrameworkOverride(): void
	{
		$appRoot = $this->makeScratchAppRoot();
		$devRoot = $this->makeDirectory('packages-dev');
		$frameworkRoot = $devRoot . '/core/framework';
		$this->initializePackageRepository($frameworkRoot, 'radaptor/core/framework', 'framework', '0.1.12');

		putenv('RADAPTOR_PACKAGE_REGISTRY_ROOT=' . $this->makeRegistryRoot([
			'radaptor/core/framework' => '0.1.13',
		]));
		putenv('RADAPTOR_WORKSPACE_DEV_MODE=1');
		putenv('RADAPTOR_DEV_ROOT=' . $devRoot);
		$this->writeJson($appRoot . '/radaptor.local.json', [
			'core' => [
				'framework' => [
					'source' => [
						'type' => 'dev',
						'location' => 'core/framework',
					],
				],
			],
		]);

		$status = PackageStateInspector::getStatusForAppRoot($appRoot, false, $devRoot);
		$framework = $this->findPackage($status['packages'], 'core:framework');

		$this->assertSame('workspace-dev', $status['mode']);
		$this->assertSame($appRoot . '/radaptor.local.json', $status['local_manifest_path']);
		$this->assertSame($appRoot . '/radaptor.local.lock.json', $status['local_lock_path']);
		$this->assertSame('dev', $framework['source_type']);
		$this->assertSame($frameworkRoot, $framework['active_path']);
		$this->assertSame('0.1.12', $framework['version']);
		$this->assertIsString($framework['source_commit']);
		$this->assertFalse($framework['source_dirty']);
		$this->assertSame('behind', $framework['freshness']);
	}

	public function testStatusReportsInconsistentModeWhenLocalOverridesExistWithoutWorkspaceMode(): void
	{
		$appRoot = $this->makeScratchAppRoot();
		putenv('RADAPTOR_WORKSPACE_DEV_MODE');
		putenv('RADAPTOR_DEV_ROOT');
		PackageLocalOverrideHelper::reset();

		$this->writeJson($appRoot . '/radaptor.local.json', [
			'core' => [
				'framework' => [
					'source' => [
						'type' => 'dev',
						'location' => 'core/framework',
					],
				],
			],
		]);

		$status = PackageStateInspector::getStatusForAppRoot($appRoot);
		$framework = $this->findPackage($status['packages'], 'core:framework');

		$this->assertSame('inconsistent', $status['mode']);
		$this->assertNotEmpty($status['issues']);
		$this->assertSame('dev', $framework['source_type']);
		$this->assertNull($framework['source_commit']);
	}

	/**
	 * @param list<array<string, mixed>> $packages
	 * @return array<string, mixed>
	 */
	private function findPackage(array $packages, string $packageKey): array
	{
		foreach ($packages as $package) {
			if (($package['package_key'] ?? null) === $packageKey) {
				return $package;
			}
		}

		$this->fail("Package not found in inspector status: {$packageKey}");
	}

	private function makeScratchAppRoot(): string
	{
		$appRoot = $this->makeDirectory('app-root');

		$this->writeJson($appRoot . '/radaptor.json', [
			'manifest_version' => 1,
			'registries' => [
				'default' => [
					'url' => 'https://packages.example.invalid/registry.json',
				],
			],
			'core' => [
				'framework' => [
					'package' => 'radaptor/core/framework',
					'source' => [
						'type' => 'registry',
						'registry' => 'default',
						'version' => '^0.1.0',
					],
				],
				'cms' => [
					'package' => 'radaptor/core/cms',
					'source' => [
						'type' => 'registry',
						'registry' => 'default',
						'version' => '^0.1.0',
					],
				],
			],
		]);

		$this->writeJson($appRoot . '/radaptor.lock.json', [
			'lockfile_version' => 1,
			'core' => [
				'framework' => [
					'type' => 'core',
					'id' => 'framework',
					'package' => 'radaptor/core/framework',
					'source' => [
						'type' => 'registry',
						'registry' => 'default',
						'resolved_registry_url' => 'https://packages.example.invalid/registry.json',
						'version' => '^0.1.0',
					],
					'resolved' => [
						'type' => 'registry',
						'registry' => 'default',
						'registry_url' => 'https://packages.example.invalid/registry.json',
						'dist_url' => 'https://packages.example.invalid/packages/radaptor-core-framework/0.1.13/package.zip',
						'dist_sha256' => 'abc123',
						'path' => 'packages/registry/core/framework',
						'version' => '0.1.13',
					],
					'dependencies' => [],
				],
				'cms' => [
					'type' => 'core',
					'id' => 'cms',
					'package' => 'radaptor/core/cms',
					'source' => [
						'type' => 'registry',
						'registry' => 'default',
						'resolved_registry_url' => 'https://packages.example.invalid/registry.json',
						'version' => '^0.1.0',
					],
					'resolved' => [
						'type' => 'registry',
						'registry' => 'default',
						'registry_url' => 'https://packages.example.invalid/registry.json',
						'dist_url' => 'https://packages.example.invalid/packages/radaptor-core-cms/0.1.6/package.zip',
						'dist_sha256' => 'def456',
						'path' => 'packages/registry/core/cms',
						'version' => '0.1.6',
					],
					'dependencies' => [
						'radaptor/core/framework' => '^0.1.0',
					],
				],
			],
		]);

		return $appRoot;
	}

	/**
	 * @param array<string, string> $latestVersions
	 */
	private function makeRegistryRoot(array $latestVersions): string
	{
		$root = $this->makeDirectory('registry-root');
		$packages = [];

		foreach ($latestVersions as $packageName => $latestVersion) {
			if ($packageName === 'radaptor/core/framework') {
				$packages[$packageName] = [
					'latest' => $latestVersion,
					'versions' => [
						$latestVersion => [
							'type' => 'core',
							'id' => 'framework',
							'dist' => [
								'type' => 'zip',
								'url' => 'packages/radaptor-core-framework/' . $latestVersion . '/package.zip',
								'sha256' => 'abc123',
							],
						],
					],
				];
			} elseif ($packageName === 'radaptor/core/cms') {
				$packages[$packageName] = [
					'latest' => $latestVersion,
					'versions' => [
						$latestVersion => [
							'type' => 'core',
							'id' => 'cms',
							'dist' => [
								'type' => 'zip',
								'url' => 'packages/radaptor-core-cms/' . $latestVersion . '/package.zip',
								'sha256' => 'def456',
							],
						],
					],
				];
			}
		}

		$this->writeJson($root . '/registry.json', [
			'registry_version' => 1,
			'name' => 'Test Registry',
			'packages' => $packages,
		]);

		return $root;
	}

	private function initializePackageRepository(string $repositoryPath, string $packageName, string $id, string $version): void
	{
		if (!mkdir($repositoryPath, 0o777, true) && !is_dir($repositoryPath)) {
			$this->fail("Unable to create package repository: {$repositoryPath}");
		}

		$this->writeFile($repositoryPath . '/.registry-package.json', json_encode([
			'package' => $packageName,
			'type' => 'core',
			'id' => $id,
			'version' => $version,
		], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
		$this->writeFile($repositoryPath . '/bootstrap.php', "<?php\n");
		$this->runGit($repositoryPath, 'init');
		$this->runGit($repositoryPath, 'config', 'user.email', 'test@example.com');
		$this->runGit($repositoryPath, 'config', 'user.name', 'Test User');
		$this->runGit($repositoryPath, 'add', '.registry-package.json', 'bootstrap.php');
		$this->runGit($repositoryPath, 'commit', '-m', 'Initial package');
	}

	private function makeDirectory(string $prefix): string
	{
		$directory = sys_get_temp_dir() . '/radaptor-package-status-' . $prefix . '-' . bin2hex(random_bytes(6));

		if (!mkdir($directory, 0o777, true) && !is_dir($directory)) {
			$this->fail("Unable to create directory: {$directory}");
		}

		$this->cleanupDirectories[] = $directory;

		return $directory;
	}

	private function writeJson(string $path, array $data): void
	{
		$this->writeFile($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
	}

	private function writeFile(string $path, string $content): void
	{
		$directory = dirname($path);

		if (!is_dir($directory) && !mkdir($directory, 0o777, true) && !is_dir($directory)) {
			$this->fail("Unable to create directory: {$directory}");
		}

		file_put_contents($path, $content);
	}

	private function runGit(string $repositoryPath, string ...$args): void
	{
		$command = ['git', '-c', 'safe.directory=' . $repositoryPath, '-C', $repositoryPath, ...$args];
		$escaped = array_map('escapeshellarg', $command);
		$output = [];
		$exitCode = 0;
		exec(implode(' ', $escaped) . ' 2>&1', $output, $exitCode);

		if ($exitCode !== 0) {
			$this->fail("Git command failed: " . implode(' ', $command) . "\n" . implode("\n", $output));
		}
	}

	private function removeDirectory(string $directory): void
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
				$this->removeDirectory($path);
			} elseif (file_exists($path)) {
				unlink($path);
			}
		}

		rmdir($directory);
	}
}
