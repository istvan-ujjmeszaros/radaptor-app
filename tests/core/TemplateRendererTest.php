<?php

use PHPUnit\Framework\TestCase;

/**
 * Tests for the pluggable template renderer system.
 */
class TemplateRendererTest extends TestCase
{
	private string $fixturesPath;

	protected function setUp(): void
	{
		$this->fixturesPath = DEPLOY_ROOT . 'tests/fixtures/templates/';
		$this->resetBladeFactory();
	}

	protected function tearDown(): void
	{
		TestHelperEnvironment::revertEnvironmentVariable('RADAPTOR_TEMPLATE_CACHE_ROOT');
		$this->resetBladeFactory();
	}

	/**
	 * Create a minimal mock Template object for testing.
	 */
	private function _createMockTemplateContext(): Template
	{
		// Use a known template that exists
		return new Template('_missing');
	}

	// ------------------------------------------
	// PHP Renderer Tests
	// ------------------------------------------

	public function testPhpRendererExtension(): void
	{
		$this->assertEquals('.php', TemplateRendererPhp::getFileExtension());
	}

	public function testPhpRendererPriority(): void
	{
		$this->assertEquals(0, TemplateRendererPhp::getPriority());
	}

	public function testPhpRendererOutput(): void
	{
		$props = ['name' => 'World', 'items' => ['Apple', 'Banana', 'Cherry']];
		$templatePath = $this->fixturesPath . 'template.testPhp.php';

		$output = TemplateRendererPhp::render($templatePath, $props, $this->_createMockTemplateContext());

		$this->assertStringContainsString('Hello World', $output);
		$this->assertStringContainsString('<li>Apple</li>', $output);
		$this->assertStringContainsString('<li>Banana</li>', $output);
		$this->assertStringContainsString('<li>Cherry</li>', $output);
		$this->assertStringContainsString('class="test-container"', $output);
	}

	public function testPhpRendererEscapesHtml(): void
	{
		$props = ['name' => '<script>alert("XSS")</script>', 'items' => []];
		$templatePath = $this->fixturesPath . 'template.testPhp.php';

		$output = TemplateRendererPhp::render($templatePath, $props, $this->_createMockTemplateContext());

		// Should be escaped, not raw
		$this->assertStringNotContainsString('<script>', $output);
		$this->assertStringContainsString('&lt;script&gt;', $output);
	}

	// ------------------------------------------
	// Blade Renderer Tests
	// ------------------------------------------

	public function testBladeRendererExtension(): void
	{
		$this->assertEquals('.blade.php', TemplateRendererBlade::getFileExtension());
	}

	public function testBladeRendererPriority(): void
	{
		$this->assertEquals(10, TemplateRendererBlade::getPriority());
	}

	public function testBladeRendererOutput(): void
	{
		$props = ['name' => 'Blade User', 'items' => ['One', 'Two', 'Three']];
		$templatePath = $this->fixturesPath . 'template.testBlade.blade.php';

		$output = TemplateRendererBlade::render($templatePath, $props, $this->_createMockTemplateContext());

		$this->assertStringContainsString('Hello Blade User', $output);
		$this->assertStringContainsString('<li>One</li>', $output);
		$this->assertStringContainsString('<li>Two</li>', $output);
		$this->assertStringContainsString('<li>Three</li>', $output);
	}

	public function testBladeRendererEscapesHtml(): void
	{
		$props = ['name' => '<script>alert("XSS")</script>', 'items' => []];
		$templatePath = $this->fixturesPath . 'template.testBlade.blade.php';

		$output = TemplateRendererBlade::render($templatePath, $props, $this->_createMockTemplateContext());

		// Blade auto-escapes with {{ }}
		$this->assertStringNotContainsString('<script>', $output);
		$this->assertStringContainsString('&lt;script&gt;', $output);
	}

	public function testBladeRendererUsesOverrideCacheRoot(): void
	{
		$cacheRoot = sys_get_temp_dir() . '/radaptor-template-cache-' . bin2hex(random_bytes(8));
		TestHelperEnvironment::setEnvironmentVariable('RADAPTOR_TEMPLATE_CACHE_ROOT', $cacheRoot);

		$cachePath = $this->invokeStaticMethod(TemplateRendererBlade::class, 'resolveCachePath');

		$this->assertSame($cacheRoot . '/views', $cachePath);
		$this->assertDirectoryExists($cachePath);
		$this->removeDirectory($cacheRoot);
	}

	// ------------------------------------------
	// Twig Renderer Tests
	// ------------------------------------------

	public function testTwigRendererExtension(): void
	{
		$this->assertEquals('.twig', TemplateRendererTwig::getFileExtension());
	}

	public function testTwigRendererPriority(): void
	{
		$this->assertEquals(10, TemplateRendererTwig::getPriority());
	}

	public function testTwigRendererOutput(): void
	{
		$props = ['name' => 'Twig User', 'items' => ['Alpha', 'Beta', 'Gamma']];
		$templatePath = $this->fixturesPath . 'template.testTwig.twig';

		$output = TemplateRendererTwig::render($templatePath, $props, $this->_createMockTemplateContext());

		$this->assertStringContainsString('Hello Twig User', $output);
		$this->assertStringContainsString('<li>Alpha</li>', $output);
		$this->assertStringContainsString('<li>Beta</li>', $output);
		$this->assertStringContainsString('<li>Gamma</li>', $output);
	}

	public function testTwigRendererEscapesHtml(): void
	{
		$props = ['name' => '<script>alert("XSS")</script>', 'items' => []];
		$templatePath = $this->fixturesPath . 'template.testTwig.twig';

		$output = TemplateRendererTwig::render($templatePath, $props, $this->_createMockTemplateContext());

		// Twig auto-escapes by default
		$this->assertStringNotContainsString('<script>', $output);
		$this->assertStringContainsString('&lt;script&gt;', $output);
	}

	public function testTwigRendererUsesOverrideCacheRoot(): void
	{
		$cacheRoot = sys_get_temp_dir() . '/radaptor-template-cache-' . bin2hex(random_bytes(8));
		TestHelperEnvironment::setEnvironmentVariable('RADAPTOR_TEMPLATE_CACHE_ROOT', $cacheRoot);

		$cachePath = $this->invokeStaticMethod(TemplateRendererTwig::class, 'resolveCachePath');

		$this->assertSame($cacheRoot . '/twig', $cachePath);
		$this->assertDirectoryExists($cachePath);
		$this->removeDirectory($cacheRoot);
	}

	// ------------------------------------------
	// Registry Tests
	// ------------------------------------------

	public function testRendererRegistryResolvesBladeBeforePhp(): void
	{
		// .blade.php should resolve to Blade renderer, not PHP
		$result = TemplateRenderers::getRendererForPath('template.foo.blade.php');
		$this->assertEquals('TemplateRendererBlade', $result);
	}

	public function testRendererRegistryResolvesTwig(): void
	{
		$result = TemplateRenderers::getRendererForPath('template.foo.twig');
		$this->assertEquals('TemplateRendererTwig', $result);
	}

	public function testRendererRegistryResolvesPhp(): void
	{
		$result = TemplateRenderers::getRendererForPath('template.foo.php');
		$this->assertEquals('TemplateRendererPhp', $result);
	}

	public function testRendererRegistryDefaultsToPhp(): void
	{
		// Unknown extension should default to PHP
		$result = TemplateRenderers::getRendererForPath('template.foo.unknown');
		$this->assertEquals('TemplateRendererPhp', $result);
	}

	public function testRendererRegistryExtensionOrder(): void
	{
		$extensions = TemplateRenderers::getExtensions();

		// .blade.php should come before .php (higher priority)
		$bladeIndex = array_search('.blade.php', $extensions);
		$phpIndex = array_search('.php', $extensions);

		$this->assertNotFalse($bladeIndex, '.blade.php should be in extension list');
		$this->assertNotFalse($phpIndex, '.php should be in extension list');
		$this->assertLessThan($phpIndex, $bladeIndex, '.blade.php should be checked before .php');
	}

	public function testTemplateListRendererLookup(): void
	{
		// All existing PHP templates should resolve to PHP renderer
		$renderer = TemplateList::getRendererForTemplate('_missing');
		$this->assertEquals('TemplateRendererPhp', $renderer);
	}

	private function resetBladeFactory(): void
	{
		$reflection = new ReflectionProperty(TemplateRendererBlade::class, 'factory');
		$reflection->setValue(null, null);
	}

	private function removeDirectory(string $directory): void
	{
		if (!is_dir($directory)) {
			return;
		}

		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
			RecursiveIteratorIterator::CHILD_FIRST
		);

		foreach ($iterator as $item) {
			if ($item->isDir()) {
				rmdir($item->getPathname());
			} else {
				unlink($item->getPathname());
			}
		}

		rmdir($directory);
	}

	private function invokeStaticMethod(string $class, string $method): mixed
	{
		$reflection = new ReflectionMethod($class, $method);

		return $reflection->invoke(null);
	}
}
