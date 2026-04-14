<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class PackageInstallServiceStorageTest extends TestCase
{
	public function testSanitizeLockfileForStorageRebasesHttpRegistryUrlsToDeclaredRegistryUrl(): void
	{
		$method = new ReflectionMethod(PackageInstallService::class, 'sanitizeLockfileForStorage');
		$method->setAccessible(true);

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
							'dist_url' => 'http://host.docker.internal:8091/packages/radaptor-core-framework/0.1.0/plugin.zip',
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
			'https://packages.radaptor.com/packages/radaptor-core-framework/0.1.0/plugin.zip',
			$sanitized['packages']['core:framework']['resolved']['dist_url']
		);
	}

	public function testSanitizeLockfileForStorageRebasesFileRegistryUrlsToDeclaredRegistryUrl(): void
	{
		$method = new ReflectionMethod(PackageInstallService::class, 'sanitizeLockfileForStorage');
		$method->setAccessible(true);

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
							'dist_url' => 'file:///app/tmp/radaptor-local-registry/packages/radaptor-core-framework/0.1.6/plugin.zip',
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
			'https://packages.radaptor.com/packages/radaptor-core-framework/0.1.6/plugin.zip',
			$sanitized['packages']['core:framework']['resolved']['dist_url']
		);
	}

	public function testSanitizeLockfileForStorageKeepsAbsoluteNonRegistryArtifactUrlsUnchanged(): void
	{
		$method = new ReflectionMethod(PackageInstallService::class, 'sanitizeLockfileForStorage');
		$method->setAccessible(true);

		$declaredRegistryUrl = 'https://packages.radaptor.com/registry.json';
		$cdnUrl = 'https://cdn.example.com/radaptor-core-framework/0.1.6/plugin.zip';
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
		$method->setAccessible(true);

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
							'dist_url' => 'https://packages.example.invalid/packages/radaptor-core-framework/0.1.6/plugin.zip',
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
			'https://packages.radaptor.com/packages/radaptor-core-framework/0.1.6/plugin.zip',
			$sanitized['packages']['core:framework']['resolved']['dist_url']
		);
	}

	public function testSanitizeLockfileForStoragePreservesArtifactQueryAndFragmentWhenRebasingRegistryUrls(): void
	{
		$method = new ReflectionMethod(PackageInstallService::class, 'sanitizeLockfileForStorage');
		$method->setAccessible(true);

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
							'dist_url' => 'http://host.docker.internal:8091/packages/radaptor-core-framework/0.1.0/plugin.zip?signature=abc123#download',
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
			'https://packages.radaptor.com/packages/radaptor-core-framework/0.1.0/plugin.zip?signature=abc123#download',
			$sanitized['packages']['core:framework']['resolved']['dist_url']
		);
	}

	public function testNormalizePathDoesNotFollowSymlinks(): void
	{
		$method = new ReflectionMethod(PackageInstallService::class, 'normalizePath');
		$method->setAccessible(true);

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
		$method->setAccessible(true);

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
		$method->setAccessible(true);

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('not under packages/registry/');

		$method->invoke(null, DEPLOY_ROOT . 'packages/dev/core/framework', 'test');
	}

	public function testAssertRegistryTargetSafeRejectsPathOutsideDeployRoot(): void
	{
		$method = new ReflectionMethod(PackageInstallService::class, 'assertRegistryTargetSafe');
		$method->setAccessible(true);

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('outside DEPLOY_ROOT');

		$method->invoke(null, '/outside/deploy/root/packages/registry/core/framework', 'test');
	}

	public function testAssertRegistryTargetSafeAcceptsValidRegistryPath(): void
	{
		$method = new ReflectionMethod(PackageInstallService::class, 'assertRegistryTargetSafe');
		$method->setAccessible(true);

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
		$method->setAccessible(true);

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
							'dist_url' => 'http://host.docker.internal:8091/packages/radaptor-core-framework/0.1.0/plugin.zip',
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
			'https://ci-user:ci-pass@packages.radaptor.com/packages/radaptor-core-framework/0.1.0/plugin.zip',
			$sanitized['packages']['core:framework']['resolved']['dist_url']
		);
	}
}
