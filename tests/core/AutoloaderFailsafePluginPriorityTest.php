<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class AutoloaderFailsafePluginPriorityTest extends TestCase
{
	/** @var string[] */
	private array $cleanupDirectories = [];

	protected function tearDown(): void
	{
		AutoloaderFailsafe::reset();

		foreach (array_reverse($this->cleanupDirectories) as $directory) {
			$this->removeDirectoryIfExists($directory);
		}

		$this->cleanupDirectories = [];

		parent::tearDown();
	}

	public function testDevPluginClassOverridesRegistryPluginClassInAutoloaderMap(): void
	{
		$plugin_id = 'shadow-test-' . bin2hex(random_bytes(4));
		$dev_dir = DEPLOY_ROOT . 'plugins/dev/' . $plugin_id;
		$registry_dir = DEPLOY_ROOT . 'plugins/registry/' . $plugin_id;
		$dev_file = $dev_dir . '/classes/class.PluginPriorityShadow.php';
		$registry_file = $registry_dir . '/classes/class.PluginPriorityShadow.php';

		$this->createPhpFile(
			$dev_file,
			"<?php\nclass PluginPriorityShadow {}\n"
		);
		$this->createPhpFile(
			$registry_file,
			"<?php\nclass PluginPriorityShadow {}\n"
		);

		$this->cleanupDirectories[] = $dev_dir;
		$this->cleanupDirectories[] = $registry_dir;

		AutoloaderFailsafe::reset();
		$autoload_map = AutoloaderFailsafe::getAutoloadMap();

		$this->assertArrayHasKey('PluginPriorityShadow', $autoload_map);
		$this->assertSame($dev_file, $autoload_map['PluginPriorityShadow']);
	}

	private function createPhpFile(string $path, string $content): void
	{
		$directory = dirname($path);

		if (!is_dir($directory)) {
			mkdir($directory, 0777, true);
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
