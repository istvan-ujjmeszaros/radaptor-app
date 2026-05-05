<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class LayoutTemplateContractInspectorTest extends TestCase
{
	public function testValidLayoutTemplatePassesContract(): void
	{
		$result = LayoutTemplateContractInspector::inspectFile($this->writeLayoutFixture(<<<'PHP'
			<?php
			?>
			<!doctype html>
			<html>
			<head>
				<?= $this->getCss() ?>
				<?= $this->getJsTop() ?>
			</head>
			<body>
					<?= $this->fetchSlot('content') ?>
					<?= $this->fetchSlot('page_chrome') ?>
					<?= $this->getJs() ?>
					<?= $this->fetchClosingHtml() ?>
				</body>
			</html>
			PHP));

		$this->assertSame('ok', $result['status']);
		$this->assertSame([], $result['missing']);
		$this->assertSame([], $result['violations']);
	}

	public function testMissingRequiredRendererCallFailsContract(): void
	{
		$result = LayoutTemplateContractInspector::inspectFile($this->writeLayoutFixture(<<<'PHP'
			<?php
			?>
			<!doctype html>
			<html>
			<head>
				<?= $this->getCss() ?>
			</head>
			<body>
					<?= $this->fetchSlot('page_chrome') ?>
					<?= $this->getJs() ?>
					<?= $this->fetchClosingHtml() ?>
				</body>
			</html>
			PHP));

		$this->assertSame('error', $result['status']);
		$this->assertContains('getJsTop', $result['missing']);
	}

	public function testExplicitHeaderSkipWithReasonPassesContract(): void
	{
		$result = LayoutTemplateContractInspector::inspectFile($this->writeLayoutFixture(<<<'PHP'
			<?php
			/**
			 * @radaptor-layout-skip getJsTop reason="This non-browser export shell intentionally has no head scripts."
			 */
			?>
			<!doctype html>
			<html>
			<head>
				<?= $this->getCss() ?>
			</head>
			<body>
					<?= $this->fetchSlot('page_chrome') ?>
					<?= $this->getJs() ?>
					<?= $this->fetchClosingHtml() ?>
				</body>
			</html>
			PHP));

		$this->assertSame('ok', $result['status']);
		$this->assertSame([], $result['missing']);
		$this->assertSame('getJsTop', $result['skips'][0]['item'] ?? null);
	}

	public function testExplicitHeaderSkipRequiresReason(): void
	{
		$result = LayoutTemplateContractInspector::inspectFile($this->writeLayoutFixture(<<<'PHP'
			<?php
			/**
			 * @radaptor-layout-skip getJsTop
			 */
			?>
			<!doctype html>
			<html>
			<head>
				<?= $this->getCss() ?>
			</head>
			<body>
					<?= $this->fetchSlot('page_chrome') ?>
					<?= $this->getJs() ?>
					<?= $this->fetchClosingHtml() ?>
				</body>
			</html>
			PHP));

		$this->assertSame('error', $result['status']);
		$this->assertContains('getJsTop', $result['missing']);
		$this->assertContains('Layout contract skip for getJsTop must include a non-empty reason.', $result['violations']);
	}

	public function testTopScriptsMustRenderBeforeHeadClose(): void
	{
		$result = LayoutTemplateContractInspector::inspectFile($this->writeLayoutFixture(<<<'PHP'
			<?php
			?>
			<!doctype html>
			<html>
			<head>
				<?= $this->getCss() ?>
			</head>
			<body>
				<?= $this->getJsTop() ?>
					<?= $this->fetchSlot('page_chrome') ?>
					<?= $this->getJs() ?>
					<?= $this->fetchClosingHtml() ?>
				</body>
			</html>
			PHP));

		$this->assertSame('error', $result['status']);
		$this->assertContains('getJsTop must be rendered before </head>.', $result['violations']);
	}

	public function testMissingHeadCloseFailsPlacementContract(): void
	{
		$result = LayoutTemplateContractInspector::inspectFile($this->writeLayoutFixture(<<<'PHP'
			<?php
			?>
			<!doctype html>
			<html>
			<head>
				<?= $this->getCss() ?>
				<?= $this->getJsTop() ?>
			<body>
					<?= $this->fetchSlot('page_chrome') ?>
					<?= $this->getJs() ?>
					<?= $this->fetchClosingHtml() ?>
				</body>
			</html>
			PHP));

		$this->assertSame('error', $result['status']);
		$this->assertContains('Layout is missing </head> close tag, cannot enforce script placement.', $result['violations']);
	}

	public function testRenderSystemMessagesIsNotRequiredByLayoutContract(): void
	{
		$result = LayoutTemplateContractInspector::inspectFile($this->writeLayoutFixture(<<<'PHP'
			<?php
			?>
			<!doctype html>
			<html>
			<head>
				<?= $this->getCss() ?>
				<?= $this->getJsTop() ?>
			</head>
				<body>
					<?= $this->fetchSlot('page_chrome') ?>
					<?= $this->getJs() ?>
					<?= $this->fetchClosingHtml() ?>
				</body>
			</html>
			PHP));

		$this->assertSame('ok', $result['status']);
		$this->assertNotContains('renderSystemMessages', $result['missing']);
	}

	private function writeLayoutFixture(string $source): string
	{
		$path = tempnam(sys_get_temp_dir(), 'radaptor_layout_contract_');
		$this->assertIsString($path);
		file_put_contents($path, $source);

		$this->registerFileForCleanup($path);

		return $path;
	}

	private function registerFileForCleanup(string $path): void
	{
		$this->assertFileExists($path);
		register_shutdown_function(static function () use ($path): void {
			@unlink($path);
		});
	}
}
