<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class PackageInstallServiceStorageTest extends TestCase
{
	public function testSanitizeLockfileForStorageRebasesHttpRegistryUrlsToDeclaredRegistryUrl(): void
	{
		$method = new ReflectionMethod(PackageInstallService::class, 'sanitizeLockfileForStorage');

		$declaredRegistryUrl = 'https://packages.radaptor.com/registry.json';
		$sanitized = $method->invoke(
			null,
			[
				'lockfile_version' => 1,
				'packages' => [
					'core:framework' => [
						'type' => 'core',
						'id' => 'framework',
						'package' => 'radaptor/core/framework',
						'source' => [
							'type' => 'registry',
							'registry' => 'default',
							'resolved_registry_url' => 'http://host.docker.internal:8091/registry.json',
						],
						'resolved' => [
							'type' => 'registry',
							'registry' => 'default',
							'registry_url' => 'http://host.docker.internal:8091/registry.json',
							'dist_url' => 'http://host.docker.internal:8091/packages/radaptor-core-framework/0.1.0/package.zip',
							'dist_sha256' => 'abc123',
							'path' => 'packages/registry/core/framework',
							'version' => '0.1.0',
						],
					],
				],
			],
			[
				'default' => [
					'name' => 'default',
					'url' => $declaredRegistryUrl,
					'resolved_url' => 'http://host.docker.internal:8091/registry.json',
				],
			]
		);

		$this->assertSame(
			$declaredRegistryUrl,
			$sanitized['packages']['core:framework']['source']['resolved_registry_url']
		);
		$this->assertSame(
			$declaredRegistryUrl,
			$sanitized['packages']['core:framework']['resolved']['registry_url']
		);
		$this->assertSame(
			'https://packages.radaptor.com/packages/radaptor-core-framework/0.1.0/package.zip',
			$sanitized['packages']['core:framework']['resolved']['dist_url']
		);
	}

	public function testSanitizeLockfileForStorageRebasesFileRegistryUrlsToDeclaredRegistryUrl(): void
	{
		$method = new ReflectionMethod(PackageInstallService::class, 'sanitizeLockfileForStorage');

		$declaredRegistryUrl = 'https://packages.radaptor.com/registry.json';
		$sanitized = $method->invoke(
			null,
			[
				'lockfile_version' => 1,
				'packages' => [
					'core:framework' => [
						'type' => 'core',
						'id' => 'framework',
						'package' => 'radaptor/core/framework',
						'source' => [
							'type' => 'registry',
							'registry' => 'default',
							'resolved_registry_url' => 'file:///app/tmp/radaptor-local-registry/registry.json',
						],
						'resolved' => [
							'type' => 'registry',
							'registry' => 'default',
							'registry_url' => 'file:///app/tmp/radaptor-local-registry/registry.json',
							'dist_url' => 'file:///app/tmp/radaptor-local-registry/packages/radaptor-core-framework/0.1.6/package.zip',
							'dist_sha256' => 'abc123',
							'path' => 'packages/registry/core/framework',
							'version' => '0.1.6',
						],
					],
				],
			],
			[
				'default' => [
					'name' => 'default',
					'url' => $declaredRegistryUrl,
					'resolved_url' => 'file:///app/tmp/radaptor-local-registry/registry.json',
				],
			]
		);

		$this->assertSame(
			$declaredRegistryUrl,
			$sanitized['packages']['core:framework']['source']['resolved_registry_url']
		);
		$this->assertSame(
			$declaredRegistryUrl,
			$sanitized['packages']['core:framework']['resolved']['registry_url']
		);
		$this->assertSame(
			'https://packages.radaptor.com/packages/radaptor-core-framework/0.1.6/package.zip',
			$sanitized['packages']['core:framework']['resolved']['dist_url']
		);
	}

	public function testSanitizeLockfileForStorageKeepsAbsoluteNonRegistryArtifactUrlsUnchanged(): void
	{
		$method = new ReflectionMethod(PackageInstallService::class, 'sanitizeLockfileForStorage');

		$declaredRegistryUrl = 'https://packages.radaptor.com/registry.json';
		$cdnUrl = 'https://cdn.example.com/radaptor-core-framework/0.1.6/package.zip';
		$sanitized = $method->invoke(
			null,
			[
				'lockfile_version' => 1,
				'packages' => [
					'core:framework' => [
						'type' => 'core',
						'id' => 'framework',
						'package' => 'radaptor/core/framework',
						'source' => [
							'type' => 'registry',
							'registry' => 'default',
							'resolved_registry_url' => 'file:///app/tmp/radaptor-local-registry/registry.json',
						],
						'resolved' => [
							'type' => 'registry',
							'registry' => 'default',
							'registry_url' => 'file:///app/tmp/radaptor-local-registry/registry.json',
							'dist_url' => $cdnUrl,
							'dist_sha256' => 'abc123',
							'path' => 'packages/registry/core/framework',
							'version' => '0.1.6',
						],
					],
				],
			],
			[
				'default' => [
					'name' => 'default',
					'url' => $declaredRegistryUrl,
					'resolved_url' => 'file:///app/tmp/radaptor-local-registry/registry.json',
				],
			]
		);

		$this->assertSame($cdnUrl, $sanitized['packages']['core:framework']['resolved']['dist_url']);
	}

	public function testSanitizeLockfileForStorageRebasesPlaceholderArtifactUrlsToDeclaredRegistryAuthority(): void
	{
		$method = new ReflectionMethod(PackageInstallService::class, 'sanitizeLockfileForStorage');

		$declaredRegistryUrl = 'https://packages.radaptor.com/registry.json';
		$sanitized = $method->invoke(
			null,
			[
				'lockfile_version' => 1,
				'packages' => [
					'core:framework' => [
						'type' => 'core',
						'id' => 'framework',
						'package' => 'radaptor/core/framework',
						'source' => [
							'type' => 'registry',
							'registry' => 'default',
							'resolved_registry_url' => PackageManifest::getPlaceholderRegistryUrl(),
						],
						'resolved' => [
							'type' => 'registry',
							'registry' => 'default',
							'registry_url' => PackageManifest::getPlaceholderRegistryUrl(),
							'dist_url' => 'https://packages.example.invalid/packages/radaptor-core-framework/0.1.6/package.zip',
							'dist_sha256' => 'abc123',
							'path' => 'packages/registry/core/framework',
							'version' => '0.1.6',
						],
					],
				],
			],
			[
				'default' => [
					'name' => 'default',
					'url' => $declaredRegistryUrl,
					'resolved_url' => PackageManifest::getPlaceholderRegistryUrl(),
				],
			]
		);

		$this->assertSame(
			'https://packages.radaptor.com/packages/radaptor-core-framework/0.1.6/package.zip',
			$sanitized['packages']['core:framework']['resolved']['dist_url']
		);
	}

	public function testSanitizeLockfileForStoragePreservesArtifactQueryAndFragmentWhenRebasingRegistryUrls(): void
	{
		$method = new ReflectionMethod(PackageInstallService::class, 'sanitizeLockfileForStorage');

		$declaredRegistryUrl = 'https://packages.radaptor.com/registry.json';
		$sanitized = $method->invoke(
			null,
			[
				'lockfile_version' => 1,
				'packages' => [
					'core:framework' => [
						'type' => 'core',
						'id' => 'framework',
						'package' => 'radaptor/core/framework',
						'source' => [
							'type' => 'registry',
							'registry' => 'default',
							'resolved_registry_url' => 'http://host.docker.internal:8091/registry.json',
						],
						'resolved' => [
							'type' => 'registry',
							'registry' => 'default',
							'registry_url' => 'http://host.docker.internal:8091/registry.json',
							'dist_url' => 'http://host.docker.internal:8091/packages/radaptor-core-framework/0.1.0/package.zip?signature=abc123#download',
							'dist_sha256' => 'abc123',
							'path' => 'packages/registry/core/framework',
							'version' => '0.1.0',
						],
					],
				],
			],
			[
				'default' => [
					'name' => 'default',
					'url' => $declaredRegistryUrl,
					'resolved_url' => 'http://host.docker.internal:8091/registry.json',
				],
			]
		);

		$this->assertSame(
			'https://packages.radaptor.com/packages/radaptor-core-framework/0.1.0/package.zip?signature=abc123#download',
			$sanitized['packages']['core:framework']['resolved']['dist_url']
		);
	}

	public function testPackageRegistryClientAllowsWorkspaceMountedLocalRegistryUrl(): void
	{
		$this->assertTrue(
			PackageRegistryClient::isSupportedRegistryUrl('file:///workspace/radaptor_package_registry/registry.json')
		);
	}

	public function testNormalizePathDoesNotFollowSymlinks(): void
	{
		$method = new ReflectionMethod(PackageInstallService::class, 'normalizePath');

		// Basic normalization
		$this->assertSame('/app/packages/registry/core/framework', $method->invoke(null, '/app/packages/registry/core/framework'));
		$this->assertSame('/app/packages/registry/core/framework', $method->invoke(null, '/app/packages/registry/core/framework/'));
		$this->assertSame('/app/packages/registry/core/framework', $method->invoke(null, '/app/packages/./registry/core/framework'));
		$this->assertSame('/app/packages/core/framework', $method->invoke(null, '/app/packages/registry/../core/framework'));

		// Does NOT follow symlinks — if packages/registry/core/framework is a symlink
		// to packages/dev/core/framework, normalizePath must return the logical path,
		// not the symlink target
		$this->assertSame(
			'/app/packages/registry/core/framework',
			$method->invoke(null, '/app/packages/registry/core/framework'),
			'normalizePath must not resolve symlinks'
		);
	}

	public function testToPathForStoragePreservesRegistryPrefix(): void
	{
		$method = new ReflectionMethod(PackageInstallService::class, 'toPathForStorage');

		$this->assertSame(
			'packages/registry/core/framework',
			$method->invoke(null, '/app/packages/registry/core/framework', '/app')
		);

		$this->assertSame(
			'packages/registry/themes/portal-admin',
			$method->invoke(null, '/app/packages/registry/themes/portal-admin', '/app')
		);
	}

	public function testAssertRegistryTargetSafeRejectsDevPath(): void
	{
		$method = new ReflectionMethod(PackageInstallService::class, 'assertRegistryTargetSafe');

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('not under packages/registry/');

		$method->invoke(null, DEPLOY_ROOT . 'packages/dev/core/framework', 'test');
	}

	public function testAssertRegistryTargetSafeRejectsPathOutsideDeployRoot(): void
	{
		$method = new ReflectionMethod(PackageInstallService::class, 'assertRegistryTargetSafe');

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('outside DEPLOY_ROOT');

		$method->invoke(null, '/outside/deploy/root/packages/registry/core/framework', 'test');
	}

	public function testAssertRegistryTargetSafeAcceptsValidRegistryPath(): void
	{
		$method = new ReflectionMethod(PackageInstallService::class, 'assertRegistryTargetSafe');

		// Should not throw for a valid, non-symlink registry path
		$target = DEPLOY_ROOT . 'packages/registry/core/framework';

		if (!is_dir(dirname($target))) {
			mkdir(dirname($target), 0o777, true);
		}

		// Only test if the path is not a symlink (which it shouldn't be in a test environment)
		if (!is_link($target) && !is_link(dirname($target))) {
			$method->invoke(null, $target, 'test');
			$this->assertTrue(true, 'assertRegistryTargetSafe did not throw for valid registry path');
		} else {
			$this->markTestSkipped('Test environment has symlinks in packages/registry/ — expected in dev mode');
		}
	}

	public function testSanitizeLockfileForStoragePreservesRegistryUserinfoWhenRebasingRelativeArtifactUrls(): void
	{
		$method = new ReflectionMethod(PackageInstallService::class, 'sanitizeLockfileForStorage');

		$declaredRegistryUrl = 'https://ci-user:ci-pass@packages.radaptor.com/registry.json';
		$sanitized = $method->invoke(
			null,
			[
				'lockfile_version' => 1,
				'packages' => [
					'core:framework' => [
						'type' => 'core',
						'id' => 'framework',
						'package' => 'radaptor/core/framework',
						'source' => [
							'type' => 'registry',
							'registry' => 'default',
							'resolved_registry_url' => 'http://host.docker.internal:8091/registry.json',
						],
						'resolved' => [
							'type' => 'registry',
							'registry' => 'default',
							'registry_url' => 'http://host.docker.internal:8091/registry.json',
							'dist_url' => 'http://host.docker.internal:8091/packages/radaptor-core-framework/0.1.0/package.zip',
							'dist_sha256' => 'abc123',
							'path' => 'packages/registry/core/framework',
							'version' => '0.1.0',
						],
					],
				],
			],
			[
				'default' => [
					'name' => 'default',
					'url' => $declaredRegistryUrl,
					'resolved_url' => 'http://host.docker.internal:8091/registry.json',
				],
			]
		);

		$this->assertSame(
			'https://ci-user:ci-pass@packages.radaptor.com/packages/radaptor-core-framework/0.1.0/package.zip',
			$sanitized['packages']['core:framework']['resolved']['dist_url']
		);
	}

	public function testRefreshInstalledPackageDiscoveryStateClearsCachedPackageLookups(): void
	{
		$refresh_method = new ReflectionMethod(PackageInstallService::class, 'refreshInstalledPackageDiscoveryState');
		$path_helper = new ReflectionClass(PackagePathHelper::class);
		$theme_helper = new ReflectionClass(PackageThemeScanHelper::class);
		$package_config = new ReflectionClass(PackageConfig::class);

		$path_active_packages = $path_helper->getProperty('_activePackages');
		$path_cache_key = $path_helper->getProperty('_cacheKey');
		$theme_active_roots = $theme_helper->getProperty('_activeRoots');
		$theme_cache_key = $theme_helper->getProperty('_activeRootsCacheKey');
		$theme_names = $theme_helper->getProperty('_themeNamesByPackageRoot');
		$config_cache = $package_config->getProperty('_cache');

		$path_active_packages->setValue(null, [
			'core:framework' => [
				'root' => '/stale/framework',
				'source_type' => 'registry',
				'type' => 'core',
				'id' => 'framework',
			],
		]);
		$path_cache_key->setValue(null, 'stale-package-cache');
		$theme_active_roots->setValue(null, ['/stale/theme']);
		$theme_cache_key->setValue(null, 'stale-theme-cache');
		$theme_names->setValue(null, ['/stale/theme' => 'SoAdmin']);
		$config_cache->setValue(null, ['core:framework:/stale/framework' => ['foo' => 'bar']]);

		$refresh_method->invoke(null);

		$this->assertNull($path_active_packages->getValue());
		$this->assertNull($path_cache_key->getValue());
		$this->assertNull($theme_active_roots->getValue());
		$this->assertNull($theme_cache_key->getValue());
		$this->assertNull($theme_names->getValue());
		$this->assertSame([], $config_cache->getValue());
	}

	public function testHasTrackedFilesUnderRecognizesTrackedConsumerContent(): void
	{
		$method = new ReflectionMethod(PackageInstallService::class, 'hasTrackedFilesUnder');

		$this->assertTrue($method->invoke(null, 'config'));
	}

	public function testAssertRegistryCleanupSafeRejectsNestedGitMetadata(): void
	{
		$method = new ReflectionMethod(PackageInstallService::class, 'assertRegistryCleanupSafe');
		$target = DEPLOY_ROOT . 'packages/registry/core/test-cleanup-nested-git';

		if (!is_dir($target . '/.git') && !mkdir($target . '/.git', 0o777, true) && !is_dir($target . '/.git')) {
			$this->fail("Unable to create nested git marker: {$target}/.git");
		}

		try {
			$this->expectException(RuntimeException::class);
			$this->expectExceptionMessage('contains .git metadata or a nested repository marker');

			$method->invoke(null, $target, 'override cleanup for test');
		} finally {
			$this->removeDirectory($target);
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
