<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class PackageLockfileTest extends TestCase
{
	private ?string $lock_path = null;

	protected function tearDown(): void
	{
		if (is_string($this->lock_path) && is_file($this->lock_path)) {
			unlink($this->lock_path);
		}

		if (is_string($this->lock_path)) {
			PackageLockfile::reset($this->lock_path);
		}

		parent::tearDown();
	}

	public function testLoadFromPathReflectsLatestWrittenState(): void
	{
		$lock_path = tempnam(sys_get_temp_dir(), 'package-lockfile-');
		$this->assertIsString($lock_path);
		$this->lock_path = $lock_path;

		PackageLockfile::write([
			'lockfile_version' => 1,
			'packages' => [
				'core:cms' => [
					'type' => 'core',
					'id' => 'cms',
					'package' => 'radaptor/core/cms',
					'source' => [
						'type' => 'dev',
						'path' => 'packages/dev/core/cms',
					],
					'resolved' => [
						'type' => 'dev',
						'path' => 'packages/dev/core/cms',
						'version' => '0.1.0',
					],
				],
			],
		], $this->lock_path);

		$first = PackageLockfile::loadFromPath($this->lock_path);

		$this->assertArrayHasKey('core:cms', $first['packages']);
		$this->assertArrayNotHasKey('theme:portal-admin', $first['packages']);

		PackageLockfile::write([
			'lockfile_version' => 1,
			'packages' => [
				'theme:portal-admin' => [
					'type' => 'theme',
					'id' => 'portal-admin',
					'package' => 'radaptor/themes/portal-admin',
					'source' => [
						'type' => 'dev',
						'path' => 'packages/dev/themes/portal-admin',
					],
					'resolved' => [
						'type' => 'dev',
						'path' => 'packages/dev/themes/portal-admin',
						'version' => '0.1.0',
					],
				],
			],
		], $this->lock_path);

		$second = PackageLockfile::loadFromPath($this->lock_path);

		$this->assertArrayHasKey('theme:portal-admin', $second['packages']);
		$this->assertArrayNotHasKey('core:cms', $second['packages']);
	}
}
