<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class PackageManifestRegistryOverrideTest extends TestCase
{
	private array $cleanup_files = [];

	protected function tearDown(): void
	{
		putenv('RADAPTOR_REGISTRY_URL');

		foreach ($this->cleanup_files as $file) {
			if (is_file($file)) {
				unlink($file);
			}
		}

		$this->cleanup_files = [];
	}

	public function testManifestLoadPrefersEnvironmentRegistryOverrideForDefaultRegistry(): void
	{
		$path = sys_get_temp_dir() . '/radaptor-package-manifest-' . bin2hex(random_bytes(8)) . '.json';
		$this->cleanup_files[] = $path;

		file_put_contents($path, json_encode([
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
			],
		], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");

		putenv('RADAPTOR_REGISTRY_URL=http://host.docker.internal:8091/registry.json');

		$manifest = PackageManifest::loadFromPath($path);

		$this->assertSame(
			'http://host.docker.internal:8091/registry.json',
			$manifest['registries']['default']['resolved_url']
		);
		$this->assertSame(
			'http://host.docker.internal:8091/registry.json',
			$manifest['registries']['default']['url']
		);
		$this->assertSame(
			'http://host.docker.internal:8091/registry.json',
			$manifest['packages']['core:framework']['source']['resolved_registry_url']
		);
	}
}
