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
				'plugin:audit' => [
					'type' => 'plugin',
					'id' => 'audit',
					'package' => 'radaptor/plugins/audit',
					'source' => [
						'type' => 'dev',
						'path' => 'plugins/dev/audit',
					],
					'resolved' => [
						'type' => 'dev',
						'path' => 'plugins/dev/audit',
						'version' => '0.1.0',
					],
				],
			],
		], $this->lock_path);

		$first = PackageLockfile::loadFromPath($this->lock_path);

		$this->assertArrayHasKey('plugin:audit', $first['packages']);
		$this->assertArrayNotHasKey('plugin:tracker', $first['packages']);

		PackageLockfile::write([
			'lockfile_version' => 1,
			'packages' => [
				'plugin:tracker' => [
					'type' => 'plugin',
					'id' => 'tracker',
					'package' => 'radaptor/plugins/tracker',
					'source' => [
						'type' => 'dev',
						'path' => 'plugins/dev/tracker',
					],
					'resolved' => [
						'type' => 'dev',
						'path' => 'plugins/dev/tracker',
						'version' => '0.1.0',
					],
				],
			],
		], $this->lock_path);

		$second = PackageLockfile::loadFromPath($this->lock_path);

		$this->assertArrayHasKey('plugin:tracker', $second['packages']);
		$this->assertArrayNotHasKey('plugin:audit', $second['packages']);
	}
}
