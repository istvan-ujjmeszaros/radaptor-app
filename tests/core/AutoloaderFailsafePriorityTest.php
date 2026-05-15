<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class AutoloaderFailsafePriorityTest extends TestCase
{
	/** @var string[] */
	private array $cleanupDirectories = [];

	/** @var array<string, string> */
	private array $renamedFiles = [];

	protected function tearDown(): void
	{
		AutoloaderFromGeneratedMap::reset();
		AutoloaderFailsafe::reset();

		foreach ($this->renamedFiles as $backup => $original) {
			if (file_exists($backup) && !file_exists($original)) {
				rename($backup, $original);
			}
		}

		foreach (array_reverse($this->cleanupDirectories) as $directory) {
			$this->removeDirectoryIfExists($directory);
		}

		$this->cleanupDirectories = [];
		$this->renamedFiles = [];

		parent::tearDown();
	}

	public function testFailsafeAutoloadMapIncludesNewAppClass(): void
	{
		$module_id = 'AutoloaderShadow' . bin2hex(random_bytes(4));
		$class_name = 'AutoloaderPriorityShadow' . bin2hex(random_bytes(4));
		$module_dir = DEPLOY_ROOT . 'app/modules/' . $module_id;
		$class_file = $module_dir . '/classes/class.' . $class_name . '.php';

		$this->createPhpFile(
			$class_file,
			"<?php\nclass {$class_name} {}\n"
		);

		$this->cleanupDirectories[] = $module_dir;

		AutoloaderFailsafe::reset();
		$autoload_map = AutoloaderFailsafe::getAutoloadMap();

		$this->assertArrayHasKey($class_name, $autoload_map);
		$this->assertSame($class_file, $autoload_map[$class_name]);
	}

	public function testMissingGeneratedAutoloadMapFallsBackToFailsafeAutoloader(): void
	{
		$module_id = 'GeneratedMapMissing' . bin2hex(random_bytes(4));
		$class_name = 'GeneratedMapMissingShadow' . bin2hex(random_bytes(4));
		$module_dir = DEPLOY_ROOT . 'app/modules/' . $module_id;
		$class_file = $module_dir . '/classes/class.' . $class_name . '.php';
		$autoload_path = DEPLOY_ROOT . 'generated/__autoload__.php';
		$backup_path = $autoload_path . '.bak-' . bin2hex(random_bytes(4));

		if (file_exists($autoload_path)) {
			rename($autoload_path, $backup_path);
			$this->renamedFiles[$backup_path] = $autoload_path;
		}

		$this->createPhpFile(
			$class_file,
			"<?php\nclass {$class_name} {}\n"
		);

		$this->cleanupDirectories[] = $module_dir;

		AutoloaderFromGeneratedMap::reset();
		AutoloaderFailsafe::reset();

		$this->assertFalse(class_exists($class_name, false));
		$this->assertTrue(class_exists($class_name));
	}

	private function createPhpFile(string $path, string $content): void
	{
		$directory = dirname($path);

		if (!is_dir($directory)) {
			mkdir($directory, 0o777, true);
		}

		file_put_contents($path, $content);
	}

	private function removeDirectoryIfExists(string $path): void
	{
		if (!is_dir($path)) {
			return;
		}

		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
			RecursiveIteratorIterator::CHILD_FIRST
		);

		foreach ($iterator as $item) {
			if ($item->isDir()) {
				rmdir($item->getPathname());
			} else {
				unlink($item->getPathname());
			}
		}

		rmdir($path);
	}
}
