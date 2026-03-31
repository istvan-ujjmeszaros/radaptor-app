<?php

declare(strict_types=1);

final class WebpageRenderingSmokeTest extends TransactionedTestCase
{
	private const int FIXTURE_PAGE_ID = 2;

	protected function setUp(): void
	{
		parent::setUp();

		$this->setRequestContext();
	}

	public function testPublicPageRendersExpectedLayoutAndWidgetContentThroughTreePipeline(): void
	{
		$page_id = self::FIXTURE_PAGE_ID;
		$this->insertPlainHtmlConnection($page_id, '<section id="public-tree-marker">Public tree smoke</section>');

		$output = $this->renderPage($page_id, 'Tracker', 'public_default');

		$this->assertStringContainsString('<!DOCTYPE html>', $output);
		$this->assertStringContainsString('<div id="container">', $output);
		$this->assertStringContainsString('<div class="content-full">', $output);
		$this->assertStringContainsString('<section id="public-tree-marker">Public tree smoke</section>', $output);
		$this->assertStringNotContainsString('Missing template', $output);
	}

	public function testPublicPageRendersJsonWhenRequestedViaAcceptHeader(): void
	{
		$page_id = self::FIXTURE_PAGE_ID;
		$this->insertPlainHtmlConnection($page_id, '<section id="public-tree-marker">Public tree smoke</section>');
		$this->setRequestContext(server_overrides: [
			'HTTP_ACCEPT' => 'application/json',
		]);

		$output = $this->renderPage($page_id, 'Tracker', 'public_default');
		$payload = json_decode($output, true, 512, JSON_THROW_ON_ERROR);

		$this->assertSame('hu', $payload['locale'] ?? null);
		$this->assertSame('layout_public_default', $payload['tree']['component'] ?? null);
		$this->assertStringContainsString('PlainHtml', $output);
	}

	private function setRequestContext(array $get = [], array $server_overrides = []): void
	{
		$server = array_replace([
			'REQUEST_URI' => '/tree-rendering-smoke.html',
			'HTTP_HOST' => 'localhost',
			'SERVER_PORT' => '80',
			'SERVER_PROTOCOL' => 'HTTP/1.1',
			'HTTPS' => '',
			'HTTP_ACCEPT' => 'text/html',
		], $server_overrides);

		RequestContextHolder::initializeRequest(get: $get, server: $server);
		$_SERVER = array_replace($_SERVER, $server);
	}

	public function testAdminPageRendersExpectedLayoutAndWidgetContentThroughTreePipeline(): void
	{
		$page_id = self::FIXTURE_PAGE_ID;
		$this->insertPlainHtmlConnection($page_id, '<section id="admin-tree-marker">Admin tree smoke</section>');

		$output = $this->renderPage($page_id, 'RadaptorPortalAdmin', 'admin_default');

		$this->assertStringContainsString('<!DOCTYPE html>', $output);
		$this->assertStringContainsString('<body class="admin-layout"', $output);
		$this->assertStringContainsString('<div class="admin-content">', $output);
		$this->assertStringContainsString('<section id="admin-tree-marker">Admin tree smoke</section>', $output);
		$this->assertStringNotContainsString('Missing template', $output);
	}

	public function testEditModeRendersWidgetInsertersAndEditChromeThroughTreePipeline(): void
	{
		$page_id = self::FIXTURE_PAGE_ID;
		$connection_id = $this->insertPlainHtmlConnection($page_id, '<section id="edit-tree-marker">Edit tree smoke</section>');

		$output = $this->renderPage($page_id, 'RadaptorPortalAdmin', 'admin_default', true);

		$this->assertStringContainsString('<section id="edit-tree-marker">Edit tree smoke</section>', $output);
		$this->assertStringContainsString('id="widget-' . $connection_id . '"', $output);
		$this->assertStringContainsString('class="widget-edit"', $output);
		$this->assertStringContainsString('class="widget-insert', $output);
		$this->assertGreaterThanOrEqual(2, substr_count($output, 'class="widget-insert'));
	}

	public function testMultipleWidgetsInSameSlotRenderInSeqOrder(): void
	{
		$page_id = self::FIXTURE_PAGE_ID;
		$this->insertPlainHtmlConnection($page_id, '<section id="seq-marker-1">First widget</section>', 1);
		$this->insertPlainHtmlConnection($page_id, '<section id="seq-marker-2">Second widget</section>', 2);

		$output = $this->renderPage($page_id, 'Tracker', 'public_default');

		$first_position = strpos($output, 'id="seq-marker-1"');
		$second_position = strpos($output, 'id="seq-marker-2"');

		$this->assertNotFalse($first_position);
		$this->assertNotFalse($second_position);
		$this->assertLessThan($second_position, $first_position);
	}

	private function renderPage(int $page_id, string $theme_name, string $layout_type_name, bool $editable = false): string
	{
		$composer = new WebpageRenderingSmokeComposer($page_id, $theme_name, $layout_type_name, $editable);

		return (string)$composer;
	}

	private function insertPlainHtmlConnection(int $page_id, string $content, int $seq = 1): int
	{
		$connection_id = DbHelper::insertHelper('widget_connections', [
			'page_id' => $page_id,
			'slot_name' => 'content',
			'widget_name' => WidgetList::PLAINHTML,
			'seq' => $seq,
		]);

		PlainHtml::saveSettings([
			'content' => $content,
		], $connection_id);

		return $connection_id;
	}
}

final class WebpageRenderingSmokeComposer extends WebpageView
{
	public function __construct(int $page_id, string $theme_name, string $layout_type_name, bool $editable)
	{
		$this->_id = $page_id;
		$this->_resourceData = [
			'node_id' => $page_id,
			'render_mode' => 'html',
			'title' => 'Tree Rendering Smoke Test',
			'description' => '',
			'keywords' => '',
			'robots_index' => 0,
			'robots_follow' => 0,
			'lang_id' => 'hu',
			'node_type' => 'webpage',
		];
		$this->_theme = ThemeBase::factory($theme_name);
		$this->_layoutTypeOverride = $layout_type_name;
		$this->layoutType = Layout::factory($layout_type_name);
		$this->_editMode = $editable;
	}
}
