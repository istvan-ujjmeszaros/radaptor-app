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

	public function testFitResizeKeepsLandscapeAspectRatioWithinMaximumBox(): void
	{
		$source = $this->createTempPng(1200, 800, [40, 120, 220]);
		$cache_path = null;

		try {
			$manipulator = new ImageManipulator(
				$source,
				[
					'maxWidth' => 800,
					'maxHeight' => 600,
					'outputFormat' => 'png',
					'quality' => 90,
					'sizingMethod' => 'fit',
					'enableZooming' => true,
				],
				'phpunit-image-manipulator-fit-landscape-' . bin2hex(random_bytes(6)),
				false
			);

			$cache_path = $manipulator->getImageCacheHandler()->getCacheFileAbsolutePath();
			$image_size = getimagesize($cache_path);

			$this->assertSame('', $manipulator->error);
			$this->assertIsArray($image_size);
			$this->assertSame([800, 533], [$image_size[0], $image_size[1]]);
		} finally {
			$this->cleanupCachePath($cache_path);
			@unlink($source);
		}
	}

	public function testFitResizeKeepsPortraitAspectRatioWithinMaximumBox(): void
	{
		$source = $this->createTempPng(800, 1200, [40, 120, 220]);
		$cache_path = null;

		try {
			$manipulator = new ImageManipulator(
				$source,
				[
					'maxWidth' => 800,
					'maxHeight' => 600,
					'outputFormat' => 'png',
					'quality' => 90,
					'sizingMethod' => 'fit',
					'enableZooming' => true,
				],
				'phpunit-image-manipulator-fit-portrait-' . bin2hex(random_bytes(6)),
				false
			);

			$cache_path = $manipulator->getImageCacheHandler()->getCacheFileAbsolutePath();
			$image_size = getimagesize($cache_path);

			$this->assertSame('', $manipulator->error);
			$this->assertIsArray($image_size);
			$this->assertSame([400, 600], [$image_size[0], $image_size[1]]);
		} finally {
			$this->cleanupCachePath($cache_path);
			@unlink($source);
		}
	}

	public function testCalculateFitDimensionsCoversWideTallAndNoUpscaleCases(): void
	{
		$this->assertSame(['width' => 800, 'height' => 600], ImageManipulator::calculateFitDimensions(originalWidth: 1024, originalHeight: 768, maxWidth: 800, maxHeight: 600, enableZooming: true));
		$this->assertSame(['width' => 800, 'height' => 200], ImageManipulator::calculateFitDimensions(originalWidth: 2000, originalHeight: 500, maxWidth: 800, maxHeight: 600, enableZooming: true));
		$this->assertSame(['width' => 150, 'height' => 600], ImageManipulator::calculateFitDimensions(originalWidth: 500, originalHeight: 2000, maxWidth: 800, maxHeight: 600, enableZooming: true));
		$this->assertSame(['width' => 100, 'height' => 100], ImageManipulator::calculateFitDimensions(originalWidth: 100, originalHeight: 100, maxWidth: 800, maxHeight: 600, enableZooming: false));
	}

	public function testCalculateCropDimensionsFillsTargetBoxBeforeCropping(): void
	{
		$this->assertSame([
			'scaled_width' => 200,
			'scaled_height' => 267,
			'final_width' => 200,
			'final_height' => 120,
			'offset_x' => 0,
			'offset_y' => 74,
		], ImageManipulator::calculateCropDimensions(originalWidth: 450, originalHeight: 600, maxWidth: 200, maxHeight: 120, enableZooming: true));

		$this->assertSame([
			'scaled_width' => 480,
			'scaled_height' => 120,
			'final_width' => 200,
			'final_height' => 120,
			'offset_x' => 140,
			'offset_y' => 0,
		], ImageManipulator::calculateCropDimensions(originalWidth: 2000, originalHeight: 500, maxWidth: 200, maxHeight: 120, enableZooming: true));
	}

	public function testCropResizeDoesNotLeaveEmptyPaddingForPortraitImages(): void
	{
		$source_rgb = [40, 120, 220];
		$source = $this->createTempPng(450, 600, $source_rgb);
		$cache_path = null;

		try {
			$cache_path = $source
				|> ImageManipulator::crop(maxWidth: 200, maxHeight: 120, outputFormat: 'png', quality: 90, enableZooming: true)
				|> ImageManipulator::cache(
					cacheSubdirectoryName: 'phpunit-image-manipulator-crop-portrait-' . bin2hex(random_bytes(6)),
					cachePathUseFilename: false
				);
			$image_size = getimagesize($cache_path);

			$this->assertIsArray($image_size);
			$this->assertSame([200, 120], [$image_size[0], $image_size[1]]);
			$this->assertPngPixelColor($cache_path, 199, 60, $source_rgb);
		} finally {
			$this->cleanupCachePath($cache_path);
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

	private function assertPngPixelColor(string $path, int $x, int $y, array $expected_rgb): void
	{
		$image = imagecreatefrompng($path);
		$this->assertInstanceOf(GdImage::class, $image);

		$color = imagecolorat($image, $x, $y);
		$this->assertIsInt($color);
		$actual_rgb = imagecolorsforindex($image, $color);

		$this->assertSame($expected_rgb[0], $actual_rgb['red']);
		$this->assertSame($expected_rgb[1], $actual_rgb['green']);
		$this->assertSame($expected_rgb[2], $actual_rgb['blue']);
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
