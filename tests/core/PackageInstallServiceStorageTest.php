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
}
