<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class CmsResourceSpecCompatScanServiceTest extends TestCase
{
	public function testScanReportsSpecsWithSlotsAndNoReplaceSlots(): void
	{
		$directory = DEPLOY_ROOT . 'tmp/test-resource-spec-compat-' . bin2hex(random_bytes(4));
		mkdir($directory, 0o775, true);
		$file = $directory . '/site.php';
		file_put_contents($file, <<<'PHP'
			<?php

			return [
				'version' => 1,
				'root' => '/',
				'resources' => [
					[
						'type' => 'webpage',
						'path' => '/example.html',
						'slots' => [
							'content' => [],
						],
					],
				],
			];
			PHP);

		try {
			$result = CmsResourceSpecCompatScanService::scan($directory);

			$this->assertSame('success', $result['status']);
			$this->assertSame(1, $result['scanned_files']);
			$this->assertSame(1, $result['potential_legacy_specs']);
			$this->assertSame('/example.html', $result['issues'][0]['path'] ?? null);
		} finally {
			@unlink($file);
			@rmdir($directory);
		}
	}
}
