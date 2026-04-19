<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ImageManipulatorTest extends TestCase
{
	public function testImageManipulatorWithoutWatermarkConfigurationDoesNotError(): void
	{
		$source = $this->createTempPng(12, 12, [40, 120, 220]);
		$cache_path = null;

		try {
			$manipulator = new ImageManipulator(
				$source,
				[
					'maxWidth' => 12,
					'maxHeight' => 12,
					'outputFormat' => 'png',
					'quality' => 90,
					'sizingMethod' => 'stretch',
					'enableZooming' => true,
				],
				'phpunit-image-manipulator-no-watermark-' . bin2hex(random_bytes(6)),
				false
			);

			$cache_path = $manipulator->getImageCacheHandler()->getCacheFileAbsolutePath();

			$this->assertSame('', $manipulator->error);
			$this->assertFileExists($cache_path);
		} finally {
			$this->cleanupCachePath($cache_path);
			@unlink($source);
		}
	}

	public function testImageManipulatorSupportsPluralWatermarksConfig(): void
	{
		$source = $this->createTempPng(12, 12, [40, 120, 220]);
		$watermark = $this->createTempPng(4, 4, [255, 255, 255]);
		$cache_path = null;

		try {
			$manipulator = new ImageManipulator(
				$source,
				[
					'maxWidth' => 12,
					'maxHeight' => 12,
					'outputFormat' => 'png',
					'quality' => 90,
					'sizingMethod' => 'stretch',
					'enableZooming' => true,
					'watermarks' => [
						['file' => $watermark],
					],
				],
				'phpunit-image-manipulator-watermarks-' . bin2hex(random_bytes(6)),
				false
			);

			$cache_path = $manipulator->getImageCacheHandler()->getCacheFileAbsolutePath();

			$this->assertSame('', $manipulator->error);
			$this->assertFileExists($cache_path);
		} finally {
			$this->cleanupCachePath($cache_path);
			@unlink($watermark);
			@unlink($source);
		}
	}

	private function createTempPng(int $width, int $height, array $rgb): string
	{
		$path = tempnam(sys_get_temp_dir(), 'radaptor-image-manipulator-');
		$this->assertIsString($path);

		$image = imagecreatetruecolor($width, $height);
		$this->assertInstanceOf(GdImage::class, $image);

		$color = imagecolorallocate($image, $rgb[0], $rgb[1], $rgb[2]);
		$this->assertIsInt($color);

		imagefill($image, 0, 0, $color);
		imagepng($image, $path);

		return $path;
	}

	private function cleanupCachePath(?string $cache_path): void
	{
		if (!is_string($cache_path) || $cache_path === '') {
			return;
		}

		@unlink($cache_path);
		@rmdir(dirname($cache_path));
	}
}
