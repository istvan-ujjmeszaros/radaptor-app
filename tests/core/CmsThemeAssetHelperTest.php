<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class CmsThemeAssetHelperTest extends TestCase
{
	/** @var string[] */
	private array $cleanupDirectories = [];

	protected function tearDown(): void
	{
		foreach (array_reverse($this->cleanupDirectories) as $directory) {
			$this->removeDirectory($directory);
		}

		$this->cleanupDirectories = [];
		parent::tearDown();
	}

	public function testGetThemeAssetsBaseUsesThemeNamespace(): void
	{
		$this->assertSame(
			'/assets/packages/themes/portal-admin',
			CmsThemeAssetHelper::getThemeAssetsBase('portal-admin')
		);
	}

	public function testGetStimulusAssetsBasePrefersRootNamespaceWhenJsExistsThere(): void
	{
		$theme_slug = 'test-cms-assets-root-' . bin2hex(random_bytes(4));
		$root_js_dir = DEPLOY_ROOT . 'public/www/assets/packages/' . $theme_slug . '/js';
		mkdir($root_js_dir, 0777, true);
		$this->cleanupDirectories[] = DEPLOY_ROOT . 'public/www/assets/packages/' . $theme_slug;

		$this->assertSame(
			'/assets/packages/' . $theme_slug,
			CmsThemeAssetHelper::getStimulusAssetsBase($theme_slug)
		);
	}

	public function testGetStimulusAssetsBaseFallsBackToThemeNamespaceWhenOnlyThemeControllersExist(): void
	{
		$theme_slug = 'test-cms-assets-theme-' . bin2hex(random_bytes(4));
		$controllers_dir = DEPLOY_ROOT . 'public/www/assets/packages/themes/' . $theme_slug . '/controllers';
		mkdir($controllers_dir, 0777, true);
		$this->cleanupDirectories[] = DEPLOY_ROOT . 'public/www/assets/packages/themes/' . $theme_slug;

		$this->assertSame(
			'/assets/packages/themes/' . $theme_slug,
			CmsThemeAssetHelper::getStimulusAssetsBase($theme_slug)
		);
	}

	private function removeDirectory(string $path): void
	{
		if (!is_dir($path) && !is_link($path)) {
			return;
		}

		if (is_link($path)) {
			unlink($path);
			return;
		}

		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
			RecursiveIteratorIterator::CHILD_FIRST
		);

		foreach ($iterator as $item) {
			if ($item->isDir()) {
				rmdir($item->getPathname());
				continue;
			}

			unlink($item->getPathname());
		}

		rmdir($path);
	}
}
