<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class PackageReleaseServiceTest extends TestCase
{
	private array $cleanupDirectories = [];

	protected function tearDown(): void
	{
		putenv('RADAPTOR_PACKAGE_REGISTRY_ROOT');
		putenv('RADAPTOR_WORKSPACE_ROOT');

		foreach (array_reverse($this->cleanupDirectories) as $directory) {
			$this->removeDirectoryIfExists($directory);
		}

		parent::tearDown();
	}

	public function testReleasePublishesNewImmutableVersionAndBumpsMetadata(): void
	{
		$packageRoot = $this->makeTempDirectory('package');
		$registryRoot = $this->makeTempDirectory('registry');
		$this->initializeGitRepository($packageRoot);
		$this->writeFile($packageRoot . '/.registry-package.json', json_encode([
			'package' => 'radaptor/core/tooling',
			'type' => 'core',
			'id' => 'tooling',
			'version' => '0.1.0',
		], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
		$this->writeFile($packageRoot . '/src/Tooling.php', "<?php\n");
		$this->runGit($packageRoot, 'add', '.registry-package.json', 'src/Tooling.php');
		$this->runGit($packageRoot, 'commit', '-m', 'Initial package');
		$sourceCommit = trim($this->runGitWithOutput($packageRoot, 'rev-parse', 'HEAD'));

		$result = PackageReleaseService::releaseFromSourcePath($packageRoot, $registryRoot, false);

		$this->assertSame('0.1.0', $result['previous_version']);
		$this->assertSame('0.1.1', $result['new_version']);
		$this->assertSame($sourceCommit, $result['source_commit']);
		$this->assertNotNull($result['build']);
		$this->assertSame('0.1.1', PackageMetadataHelper::loadFromSourcePath($packageRoot)['version']);

		$registry = json_decode((string) file_get_contents($registryRoot . '/registry.json'), true, 512, JSON_THROW_ON_ERROR);
		$entry = $registry['packages']['radaptor/core/tooling']['versions']['0.1.1'];
		$this->assertSame($sourceCommit, $entry['source_commit']);
		$this->assertSame($result['released_at'], $entry['released_at']);
	}

	public function testReleaseRejectsTrackedChangesBeforeBumpingVersion(): void
	{
		$packageRoot = $this->makeTempDirectory('package');
		$registryRoot = $this->makeTempDirectory('registry');
		$this->initializeGitRepository($packageRoot);
		$this->writeFile($packageRoot . '/.registry-package.json', json_encode([
			'package' => 'radaptor/core/tooling',
			'type' => 'core',
			'id' => 'tooling',
			'version' => '0.1.0',
		], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
		$this->writeFile($packageRoot . '/src/Tooling.php', "<?php\n");
		$this->runGit($packageRoot, 'add', '.registry-package.json', 'src/Tooling.php');
		$this->runGit($packageRoot, 'commit', '-m', 'Initial package');
		$this->writeFile($packageRoot . '/src/Tooling.php', "<?php\n// dirty\n");

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Package repository has tracked changes and cannot be released');

		try {
			PackageReleaseService::releaseFromSourcePath($packageRoot, $registryRoot);
		} finally {
			$this->assertSame('0.1.0', PackageMetadataHelper::loadFromSourcePath($packageRoot)['version']);
		}
	}

	public function testReleaseRejectsUntrackedMetadataFile(): void
	{
		$packageRoot = $this->makeTempDirectory('package');
		$registryRoot = $this->makeTempDirectory('registry');
		$this->initializeGitRepository($packageRoot);
		$this->writeFile($packageRoot . '/.registry-package.json', json_encode([
			'package' => 'radaptor/core/tooling',
			'type' => 'core',
			'id' => 'tooling',
			'version' => '0.1.0',
		], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
		$this->writeFile($packageRoot . '/src/Tooling.php', "<?php\n");
		$this->runGit($packageRoot, 'add', 'src/Tooling.php');
		$this->runGit($packageRoot, 'commit', '-m', 'Tracked source only');

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Package metadata file must be tracked before release');

		PackageReleaseService::releaseFromSourcePath($packageRoot, $registryRoot);
	}

	public function testPrereleaseDryRunDoesNotMutateMetadataOrRegistry(): void
	{
		$packageRoot = $this->makeTempDirectory('package');
		$registryRoot = $this->makeTempDirectory('registry');
		$this->initializeGitRepository($packageRoot);
		$this->writeFile($packageRoot . '/.registry-package.json', json_encode([
			'package' => 'radaptor/core/tooling',
			'type' => 'core',
			'id' => 'tooling',
			'version' => '0.1.0',
		], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
		$this->writeFile($packageRoot . '/src/Tooling.php', "<?php\n");
		$this->runGit($packageRoot, 'add', '.registry-package.json', 'src/Tooling.php');
		$this->runGit($packageRoot, 'commit', '-m', 'Initial package');

		$result = PackageReleaseService::prereleaseFromSourcePath($packageRoot, 'alpha', $registryRoot, true);

		$this->assertSame('0.1.1-alpha.1', $result['new_version']);
		$this->assertNull($result['build']);
		$this->assertSame('0.1.0', PackageMetadataHelper::loadFromSourcePath($packageRoot)['version']);
		$this->assertFileDoesNotExist($registryRoot . '/registry.json');
	}

	public function testReleaseWarnsWhenWorkspaceConsumerLockFallsBehind(): void
	{
		$workspaceRoot = $this->makeTempDirectory('workspace');
		$registryRoot = $workspaceRoot . '/radaptor_package_registry';
		$consumerRoot = $workspaceRoot . '/consumer-app';
		$packageRoot = $this->makeTempDirectory('package');

		mkdir($registryRoot, 0o777, true);
		mkdir($workspaceRoot . '/packages-dev', 0o777, true);
		mkdir($consumerRoot, 0o777, true);
		$this->initializeGitRepository($packageRoot);
		$this->writeFile($packageRoot . '/.registry-package.json', json_encode([
			'package' => 'radaptor/core/tooling',
			'type' => 'core',
			'id' => 'tooling',
			'version' => '0.1.0',
		], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
		$this->writeFile($packageRoot . '/src/Tooling.php', "<?php\n");
		$this->runGit($packageRoot, 'add', '.registry-package.json', 'src/Tooling.php');
		$this->runGit($packageRoot, 'commit', '-m', 'Initial package');
		$this->initializeGitRepository($consumerRoot);
		$this->writeFile($consumerRoot . '/radaptor.json', json_encode([
			'manifest_version' => 1,
			'registries' => [
				'default' => [
					'url' => 'https://packages.radaptor.com/registry.json',
				],
			],
			'core' => [
				'tooling' => [
					'package' => 'radaptor/core/tooling',
					'source' => [
						'type' => 'registry',
						'registry' => 'default',
						'version' => '^0.1.0',
					],
				],
			],
		], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
		$this->writeFile($consumerRoot . '/radaptor.lock.json', json_encode([
			'lockfile_version' => 1,
			'core' => [
				'tooling' => [
					'type' => 'core',
					'id' => 'tooling',
					'package' => 'radaptor/core/tooling',
					'source' => [
						'type' => 'registry',
						'registry' => 'default',
						'version' => '^0.1.0',
						'resolved_registry_url' => 'https://packages.radaptor.com/registry.json',
					],
					'resolved' => [
						'type' => 'registry',
						'registry' => 'default',
						'registry_url' => 'https://packages.radaptor.com/registry.json',
						'version' => '0.1.0',
						'path' => 'packages/registry/core/tooling',
						'dist_url' => 'https://packages.radaptor.com/packages/radaptor-core-tooling/0.1.0/package.zip',
						'dist_sha256' => 'abc123',
					],
				],
			],
		], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
		$this->runGit($consumerRoot, 'add', 'radaptor.json', 'radaptor.lock.json');
		$this->runGit($consumerRoot, 'commit', '-m', 'Initial consumer');
		putenv('RADAPTOR_PACKAGE_REGISTRY_ROOT=' . $registryRoot);
		putenv('RADAPTOR_WORKSPACE_ROOT=' . $workspaceRoot);

		$result = PackageReleaseService::releaseFromSourcePath($packageRoot, $registryRoot, false);

		$this->assertNotEmpty($result['warnings']);
		$this->assertStringContainsString('consumer-app', $result['warnings'][0]);
	}

	private function initializeGitRepository(string $repositoryPath): void
	{
		$this->runGit($repositoryPath, 'init');
		$this->runGit($repositoryPath, 'config', 'user.email', 'test@example.com');
		$this->runGit($repositoryPath, 'config', 'user.name', 'Test User');
	}

	private function makeTempDirectory(string $prefix): string
	{
		$directory = sys_get_temp_dir() . '/radaptor-package-release-' . $prefix . '-' . bin2hex(random_bytes(6));

		if (!mkdir($directory, 0o777, true) && !is_dir($directory)) {
			$this->fail("Unable to create temporary directory: {$directory}");
		}

		$this->cleanupDirectories[] = $directory;

		return $directory;
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
		$this->runGitWithOutput($repositoryPath, ...$args);
	}

	private function runGitWithOutput(string $repositoryPath, string ...$args): string
	{
		$command = ['git', '-c', 'safe.directory=' . $repositoryPath, '-C', $repositoryPath, ...$args];
		$escaped = array_map('escapeshellarg', $command);
		$output = [];
		$exitCode = 0;
		exec(implode(' ', $escaped) . ' 2>&1', $output, $exitCode);

		if ($exitCode !== 0) {
			$this->fail("Git command failed: " . implode(' ', $command) . "\n" . implode("\n", $output));
		}

		return implode("\n", $output);
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
}
