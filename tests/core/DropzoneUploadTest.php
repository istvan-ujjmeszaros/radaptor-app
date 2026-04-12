<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class DropzoneUploadTest extends TestCase
{
	private array $directories_to_cleanup = [];
	private array $original_post = [];
	private array $original_files = [];

	protected function setUp(): void
	{
		$this->original_post = $_POST;
		$this->original_files = $_FILES;
	}

	protected function tearDown(): void
	{
		$_POST = $this->original_post;
		$_FILES = $this->original_files;

		foreach ($this->directories_to_cleanup as $directory) {
			$this->removeDirectory($directory);
		}

		$this->directories_to_cleanup = [];
	}

	public function testSanitizeFileNameRemovesPathSegmentsAndNullBytes(): void
	{
		$method = new ReflectionMethod(DropzoneUpload::class, 'sanitizeFileName');
		$method->setAccessible(true);

		$this->assertSame(
			'report.pdf',
			$method->invoke(null, "C:\\temp\\nested/report.pdf\0")
		);
	}

	public function testBuildUniqueFilePathAddsSuffixBeforeExtension(): void
	{
		$directory = sys_get_temp_dir() . '/radaptor-dropzone-upload-' . bin2hex(random_bytes(6));
		mkdir($directory, 0o777, true);
		$this->directories_to_cleanup[] = $directory;

		file_put_contents($directory . '/example.txt', 'existing');

		$method = new ReflectionMethod(DropzoneUpload::class, 'buildUniqueFilePath');
		$method->setAccessible(true);

		$path = $method->invoke(null, $directory, 'example.txt');

		$this->assertSame($directory . '/example(1).txt', $path);
	}

	public function testConstructorBuildsStableFallbackChunkMetadataWhenDropzoneUuidIsMissing(): void
	{
		$_POST = [];
		$_FILES = [
			'file' => [
				'name' => 'upload-smoke-big.bin',
				'size' => 3 * 1024 * 1024,
				'tmp_name' => '/tmp/php-upload-smoke',
			],
		];

		$upload = new DropzoneUpload();

		$file_id_property = new ReflectionProperty(DropzoneUpload::class, '_file_id');
		$file_id_property->setAccessible(true);
		$partition_index_property = new ReflectionProperty(DropzoneUpload::class, '_partition_index');
		$partition_index_property->setAccessible(true);
		$partition_count_property = new ReflectionProperty(DropzoneUpload::class, '_partition_count');
		$partition_count_property->setAccessible(true);
		$file_length_property = new ReflectionProperty(DropzoneUpload::class, '_file_length');
		$file_length_property->setAccessible(true);

		$this->assertSame(
			hash('sha256', implode('|', [session_id(), 'upload-smoke-big.bin', (string) (3 * 1024 * 1024)])),
			$file_id_property->getValue($upload)
		);
		$this->assertSame(0, $partition_index_property->getValue($upload));
		$this->assertSame(1, $partition_count_property->getValue($upload));
		$this->assertSame(3 * 1024 * 1024, $file_length_property->getValue($upload));
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

			$path = $directory . DIRECTORY_SEPARATOR . $item;

			if (is_dir($path)) {
				$this->removeDirectory($path);
			} else {
				@unlink($path);
			}
		}

		@rmdir($directory);
	}
}
