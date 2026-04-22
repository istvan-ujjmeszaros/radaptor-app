<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class PackageAssetsBuilderTest extends TestCase
{
	private ?string $app_root = null;

	protected function tearDown(): void
	{
		if (is_string($this->app_root)) {
			$this->removePath($this->app_root);
		}

		$this->app_root = null;
		parent::tearDown();
	}

	public function testBuildPathsAdoptsLegacyLocalOverrideLinkWithoutStateFile(): void
	{
		$this->app_root = sys_get_temp_dir() . '/package-assets-builder-' . bin2hex(random_bytes(6));
		$registry_root = $this->app_root . '/packages/registry/themes/portal-admin';
		$legacy_root = $this->app_root . '/workspace-dev/themes/portal-admin';
		$registry_asset_root = $registry_root . '/public/assets/radaptor-portal-admin';
		$target = $this->app_root . '/public/www/assets/packages/radaptor-portal-admin';
		$state_path = $this->app_root . '/generated/__package_assets__.json';

		mkdir($registry_asset_root, 0o777, true);
		file_put_contents($registry_asset_root . '/theme.css', 'body { color: black; }');
		mkdir(dirname($target), 0o777, true);
		symlink($legacy_root . '/public/assets/radaptor-portal-admin', $target);

		$this->writeLockfile(
			$this->app_root . '/radaptor.lock.json',
			$registry_root,
			'registry'
		);
		$this->writeLockfile(
			$this->app_root . '/radaptor.local.lock.json',
			$legacy_root,
			'dev'
		);

		$result = PackageAssetsBuilder::buildPaths(
			$this->app_root . '/radaptor.lock.json',
			$state_path,
			false,
			$this->app_root
		);

		$this->assertSame(1, $result['links_removed']);
		$this->assertSame(1, $result['links_created']);
		$this->assertSame($registry_asset_root, readlink($target));

		$state = json_decode((string) file_get_contents($state_path), true, 512, JSON_THROW_ON_ERROR);

		$this->assertSame(
			$registry_asset_root,
			$state['links'][$target] ?? null
		);
	}

	private function writeLockfile(string $path, string $package_root, string $source_type): void
	{
		$document = [
			'lockfile_version' => 1,
			'themes' => [
				'portal-admin' => [
					'type' => 'theme',
					'id' => 'portal-admin',
					'package' => 'radaptor/themes/portal-admin',
					'source' => [
						'type' => $source_type,
						'path' => $package_root,
					],
					'resolved' => [
						'type' => $source_type,
						'path' => $package_root,
						'version' => '0.1.0',
					],
					'dependencies' => [
						'radaptor/core/cms' => '^0.1.0',
					],
					'assets' => [
						'public' => [
							[
								'source' => 'public/assets/radaptor-portal-admin',
								'target' => 'assets/packages/radaptor-portal-admin',
							],
						],
					],
				],
			],
		];

		$directory = dirname($path);

		if (!is_dir($directory)) {
			mkdir($directory, 0o777, true);
		}

		file_put_contents(
			$path,
			json_encode($document, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n"
		);
	}

	private function removePath(string $path): void
	{
		if (!file_exists($path) && !is_link($path)) {
			return;
		}

		if (is_link($path) || is_file($path)) {
			unlink($path);

			return;
		}

		$items = scandir($path);

		if ($items === false) {
			return;
		}

		foreach ($items as $item) {
			if ($item === '.' || $item === '..') {
				continue;
			}

			$this->removePath($path . '/' . $item);
		}

		rmdir($path);
	}
}
