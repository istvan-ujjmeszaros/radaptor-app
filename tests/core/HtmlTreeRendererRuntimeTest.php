<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Verifies HtmlTreeRenderer as the HTML runtime:
 * - Asset accumulation (registerLibrary, getCss, getJsTop, etc.)
 * - Template delegation (getPageId, getTheme, etc.)
 * - SduiJsonTreeRenderer reads lang_id from render context
 */
final class HtmlTreeRendererRuntimeTest extends TestCase
{
	protected function setUp(): void
	{
		RequestContextHolder::initializeRequest();
	}

	public function testAssetAccumulationCss(): void
	{
		$renderer = new HtmlTreeRenderer();
		$renderer->registerCss('/assets/test.css');

		$css = $renderer->getCss();

		$this->assertStringContainsString('/assets/test.css', $css);
		$this->assertStringContainsString('<link', $css);
	}

	public function testAssetAccumulationJsTop(): void
	{
		$renderer = new HtmlTreeRenderer();
		$renderer->registerJs('/assets/test.js', true);

		$jsTop = $renderer->getJsTop();

		$this->assertStringContainsString('/assets/test.js', $jsTop);
		$this->assertStringNotContainsString('/assets/test.js', $renderer->getJs());
	}

	public function testAssetAccumulationJsBottom(): void
	{
		$renderer = new HtmlTreeRenderer();
		$renderer->registerJs('/assets/bottom.js', false);

		$this->assertStringContainsString('/assets/bottom.js', $renderer->getJs());
		$this->assertStringContainsString('/assets/bottom.js', $renderer->getJsBottom());
		// Not in top
		$this->assertStringNotContainsString('/assets/bottom.js', $renderer->getJsTop());
	}

	public function testLibraryDependenciesDeduplicateAssetPaths(): void
	{
		$renderer = new HtmlTreeRenderer(theme: ThemeBase::factory('RadaptorPortalAdmin'));

		$renderer->registerLibrary('__RADAPTOR_PORTAL_ADMIN_SITE');
		$renderer->registerLibrary('__RADAPTOR_PORTAL_ADMIN_SITE');

		$js = $renderer->getJs();

		$this->assertSame(1, substr_count($js, 'bootstrap.bundle.min.js'));
		$this->assertSame(1, substr_count($js, 'htmx.org@2.0.4'));
	}

	public function testTopLocalLibraryAssetKeepsAbsoluteAssetPath(): void
	{
		$renderer = new HtmlTreeRenderer();

		$renderer->registerLibrary('js:^/assets/example/top-local.js');

		$this->assertStringContainsString('/assets/example/top-local.js', $renderer->getJsTop());
		$this->assertStringNotContainsString('/assets//assets/example/top-local.js', $renderer->getJsTop());
		$this->assertStringNotContainsString('/assets/example/top-local.js', $renderer->getJs());
	}

	public function testFetchInnerHtml(): void
	{
		$renderer = new HtmlTreeRenderer();
		$renderer->registerInnerHtml('<div id="test">inner</div>');

		$this->assertStringContainsString('<div id="test">inner</div>', $renderer->fetchInnerHtml());
	}

	public function testFetchClosingHtmlIncludesI18nPayload(): void
	{
		$renderer = new HtmlTreeRenderer();
		$renderer->registerI18n('common.save');

		$closing = $renderer->fetchClosingHtml();

		$this->assertStringContainsString('window.__i18n', $closing);
		$this->assertStringContainsString('common.save', $closing);
	}

	public function testFetchClosingHtmlIncludesRegisteredHtml(): void
	{
		$renderer = new HtmlTreeRenderer();
		$renderer->registerClosingHtml('<script>console.log("closing");</script>');

		$closing = $renderer->fetchClosingHtml();

		$this->assertStringContainsString('console.log("closing")', $closing);
	}

	public function testPageMetadataGetters(): void
	{
		$theme = ThemeBase::factory('RadaptorPortalAdmin');
		$renderer = new HtmlTreeRenderer(
			theme: $theme,
			lang_id: 'en_US',
			page_id: 42,
			title: 'Test Page',
			description: 'A test description',
			pagedata: ['custom_key' => 'custom_value'],
			is_editable: true,
		);

		$this->assertSame($theme, $renderer->getTheme());
		$this->assertSame('en_US', $renderer->getLangId());
		$this->assertSame(42, $renderer->getPageId());
		$this->assertSame('Test Page', $renderer->getTitle());
		$this->assertSame('A test description', $renderer->getDescription());
		$this->assertSame('custom_value', $renderer->getPagedata('custom_key'));
		$this->assertNull($renderer->getPagedata('nonexistent'));
		$this->assertTrue($renderer->isEditable());
	}

	public function testTemplateDelegationToRenderer(): void
	{
		$theme = ThemeBase::factory('RadaptorPortalAdmin');
		$renderer = new HtmlTreeRenderer(
			theme: $theme,
			page_id: 99,
			title: 'Delegated Title',
			description: 'Delegated desc',
		);

		$template = new Template('statusMessage', $renderer);
		$template->props = ['severity' => 'info', 'message' => 'test'];

		$this->assertSame($renderer, $template->getRenderer());
		$this->assertSame(99, $template->getPageId());
		$this->assertSame('Delegated Title', $template->getTitle());
		$this->assertSame('Delegated desc', $template->getDescription());
		$this->assertSame($theme, $template->getTheme());
		$this->assertFalse($template->isEditable());
	}

	public function testTemplateProxyRegistersOnRenderer(): void
	{
		$renderer = new HtmlTreeRenderer();
		$template = new Template('statusMessage', $renderer);
		$template->props = ['severity' => 'info', 'message' => 'test'];

		$template->registerCss('/assets/via-proxy.css');

		$this->assertStringContainsString('/assets/via-proxy.css', $renderer->getCss());
	}

	public function testTemplateWithNullRendererIsGraceful(): void
	{
		$template = new Template('statusMessage', null);
		$template->props = ['severity' => 'info', 'message' => 'test'];

		// These should not throw
		$this->assertNull($template->getPageId());
		$this->assertSame('', $template->getTitle());
		$this->assertFalse($template->isEditable());
		$this->assertNull($template->getTheme());
	}

	public function testStatusMessageTemplateUsesSharedCssInsteadOfInlineStyles(): void
	{
		$renderer = new HtmlTreeRenderer(theme: ThemeBase::factory('SoAdmin'));
		$template = new Template('sdui.statusMessage', $renderer);
		$template->props = [
			'severity' => 'error',
			'message' => 'Missing widget',
			'missing' => ['widget_name' => 'Unknown widget'],
			'redirect_url' => '/admin/',
		];

		$output = $template->fetch();

		$this->assertStringContainsString('class="sdui-status sdui-status-error"', $output);
		$this->assertStringContainsString('class="sdui-status-list"', $output);
		$this->assertStringContainsString('class="sdui-status-link"', $output);
		$this->assertStringNotContainsString('style=', $output);
		$this->assertStringContainsString('/assets/_common/css/sdui-status.css', $renderer->getCss());
	}

	public function testSduiJsonTreeRendererReadsLangIdFromContext(): void
	{
		$renderer = new SduiJsonTreeRenderer();

		$node = SduiNode::create('statusMessage', ['severity' => 'info', 'message' => 'test']);
		$json = $renderer->render($node, ['lang_id' => 'hu_HU']);
		$decoded = json_decode($json, true);

		$this->assertIsArray($decoded);
		// Verify lang_id was passed through to serializer (stored as 'locale' in document)
		$this->assertSame('hu_HU', $decoded['locale'] ?? null);
	}

	public function testRendererAccumulatesAssetsDuringRender(): void
	{
		$theme = ThemeBase::factory('RadaptorPortalAdmin');
		$renderer = new HtmlTreeRenderer(theme: $theme);

		// The templateEngineDemoWrapper template registers prism CSS during rendering
		$renderer->render(SduiNode::create(
			component: 'templateEngineDemoWrapper',
			props: [
				'engineName' => 'PHP',
				'engineClass' => 'TemplateRendererPhp',
				'fileExtension' => '.php',
				'sourceCode' => '<?php echo "demo";',
			],
			slots: [
				'demo' => [SduiNode::create('statusMessage', ['severity' => 'info', 'message' => 'test'])],
			],
		));

		// Registered assets should be accessible on the renderer after render
		$css = $renderer->getCss();
		$this->assertStringContainsString('prism', $css);
	}

	public function testTemplateResolvesThemedPathViaRenderer(): void
	{
		$renderer = new HtmlTreeRenderer(theme: ThemeBase::factory('RadaptorPortalAdmin'));
		$template = new Template('sdui.form', $renderer);

		$this->assertStringContainsString('/themes/portal-admin/', $template->getTemplatePath('sdui.form'));
	}

	public function testTemplateWithoutRendererFallsBackToBaseTemplate(): void
	{
		$template = new Template('sdui.form');

		$this->assertStringContainsString('template.sdui.form.php', $template->getTemplatePath('sdui.form'));
	}

	public function testTemplateFallsBackToDefaultThemeBeforeBaseTemplate(): void
	{
		$renderer = new HtmlTreeRenderer(theme: ThemeBase::factory('_ThemeError'));
		$template = new Template('widgetInsert', $renderer);

		$this->assertStringContainsString('/themes/portal-admin/', $template->getTemplatePath('widgetInsert'));
		$this->assertStringNotContainsString('/themes/so-admin/', $template->getTemplatePath('widgetInsert'));
	}

	public function testEditableRendererFallsBackToPortalAdminAssets(): void
	{
		$renderer = new HtmlTreeRenderer(is_editable: true, theme: ThemeBase::factory('_ThemeError'));

		$css = $renderer->getCss();
		$js = $renderer->getJs();

		$this->assertStringContainsString('/assets/packages/themes/radaptor-portal-admin/css/radaptor-base.css', $css);
		$this->assertStringContainsString('/assets/packages/themes/radaptor-portal-admin/css/edit-mode.css', $css);
		$this->assertStringContainsString('bootstrap.bundle.min.js', $js);
		$this->assertStringNotContainsString('/assets/packages/themes/so-admin/', $css . $js);
	}

	public function testSoAdminInlineFormDependenciesRenderInHead(): void
	{
		$renderer = new HtmlTreeRenderer(theme: ThemeBase::factory('SoAdmin'));
		$renderer->registerLibrary('__ADMIN_SITE');
		$renderer->registerLibrary('TIPPY');

		$top_js = $renderer->getJsTop();
		$bottom_js = $renderer->getJs();

		$this->assertStringContainsString('jquery.min.js', $top_js);
		$this->assertStringContainsString('jquery-ui.min.js', $top_js);
		$this->assertStringContainsString('tippy-bundle.umd.min.js', $top_js);
		$this->assertStringNotContainsString('jquery-ui.min.js', $bottom_js);
		$this->assertStringNotContainsString('tippy-bundle.umd.min.js', $bottom_js);
	}
}
