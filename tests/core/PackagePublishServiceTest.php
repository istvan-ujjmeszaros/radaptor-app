<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class PackagePublishServiceTest extends TestCase
{
	private array $cleanupDirectories = [];

	protected function tearDown(): void
	{
		foreach (array_reverse($this->cleanupDirectories) as $directory) {
			$this->removeDirectoryIfExists($directory);
		}

		parent::tearDown();
	}

	public function testPublishFromSourcePathBuildsRegistryArtifact(): void
	{
		$packageRoot = $this->makeTempDirectory('package');
		$registryRoot = $this->makeTempDirectory('registry');
		$this->runGit($packageRoot, 'init');
		$this->writeFile($packageRoot . '/.registry-package.json', json_encode([
			'package' => 'radaptor/core/tooling',
			'type' => 'core',
			'id' => 'tooling',
			'version' => '0.1.0',
		], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
		$this->writeFile($packageRoot . '/src/Tooling.php', "<?php\n");
		$this->runGit($packageRoot, 'add', '.registry-package.json', 'src/Tooling.php');

		$result = PackagePublishService::publishFromSourcePath(
			$packageRoot,
			$registryRoot,
			'core:tooling'
		);

		$this->assertSame('core:tooling', $result['package_key']);
		$this->assertSame('radaptor/core/tooling', $result['package']);
		$this->assertSame('0.1.0', $result['version']);
		$this->assertFileExists($registryRoot . '/registry.json');
		$this->assertFileExists($result['dist_path']);

		$registry = json_decode((string) file_get_contents($registryRoot . '/registry.json'), true, 512, JSON_THROW_ON_ERROR);
		$this->assertSame(
			$result['sha256'],
			$registry['packages']['radaptor/core/tooling']['versions']['0.1.0']['dist']['sha256']
		);

		$zip = new ZipArchive();
		$this->assertTrue($zip->open($result['dist_path']) === true);
		$this->assertNotFalse($zip->locateName('.registry-package.json'));
		$this->assertNotFalse($zip->locateName('src/Tooling.php'));
		$zip->close();
	}

	private function makeTempDirectory(string $prefix): string
	{
		$directory = sys_get_temp_dir() . '/radaptor-package-publish-' . $prefix . '-' . bin2hex(random_bytes(6));

		if (!mkdir($directory, 0777, true) && !is_dir($directory)) {
			$this->fail("Unable to create temporary directory: {$directory}");
		}

		$this->cleanupDirectories[] = $directory;

		return $directory;
	}

	private function writeFile(string $path, string $content): void
	{
		$directory = dirname($path);

		if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
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
