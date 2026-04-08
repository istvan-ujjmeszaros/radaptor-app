<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Verifies that the floating admin dropdown renders for an authenticated admin
 * user across all themes, and that edit mode enables widget edit chrome.
 *
 * Requirements:
 * - User must be logged in with ResourceAcl edit access
 * - Page must have a non-null page_id
 * - admin_developer + ROLE_SYSTEM_DEVELOPER bypasses ACL when
 *   DEV_DEVELOPERS_CAN_ACCESS_ALL_RESOURCES=true
 */
final class AdminDropdownSmokeTest extends TransactionedTestCase
{
	private const int FIXTURE_PAGE_ID = 2;

	protected function setUp(): void
	{
		parent::setUp();
		$this->setRequestContext();
	}

	protected function tearDown(): void
	{
		$this->impersonate(null);
		parent::tearDown();
	}

	// -------------------------------------------------------------------------
	// Data providers
	// -------------------------------------------------------------------------

	/**
	 * @return array<string, array{string, string}>
	 */
	public static function adminDropdownThemeProvider(): array
	{
		return [
			'RadaptorPortalAdmin' => ['RadaptorPortalAdmin', 'admin-dropdown-container'],
			'SoAdmin'             => ['SoAdmin', 'admin_dropdown_icon'],
			'Tracker'             => ['Tracker', 'admin_dropdown_icon'],
		];
	}

	// -------------------------------------------------------------------------
	// Admin dropdown visibility
	// -------------------------------------------------------------------------

	#[DataProvider('adminDropdownThemeProvider')]
	public function testAdminDropdownRendersForAuthenticatedAdminAcrossThemes(
		string $theme_name,
		string $dropdown_marker,
	): void {
		$page_id = self::FIXTURE_PAGE_ID;
		$this->insertPlainHtmlConnection($page_id, '<section id="smoke-content">Content</section>');
		$this->impersonateDevOnlyUser();

		$output = $this->renderPage($page_id, $theme_name, 'admin_default');

		$this->assertStringContainsString(
			$dropdown_marker,
			$output,
			"Admin dropdown marker '{$dropdown_marker}' not found for theme {$theme_name}",
		);
		$this->assertStringNotContainsString('Missing template', $output);
	}

	#[DataProvider('adminDropdownThemeProvider')]
	public function testAdminDropdownDoesNotRenderForAnonymousUser(
		string $theme_name,
		string $dropdown_marker,
	): void {
		$page_id = self::FIXTURE_PAGE_ID;
		$this->insertPlainHtmlConnection($page_id, '<section id="smoke-content">Content</section>');
		$this->impersonate(null);

		$output = $this->renderPage($page_id, $theme_name, 'admin_default');

		$this->assertStringNotContainsString($dropdown_marker, $output);
	}

	// -------------------------------------------------------------------------
	// Edit mode chrome
	// -------------------------------------------------------------------------

	#[DataProvider('adminDropdownThemeProvider')]
	public function testEditModeRendersWidgetInsertersAndEditChromeAcrossThemes(
		string $theme_name,
		string $_dropdownMarker,
	): void {
		$page_id = self::FIXTURE_PAGE_ID;
		$connection_id = $this->insertPlainHtmlConnection($page_id, '<section id="edit-marker">Edit smoke</section>');
		$this->impersonateDevOnlyUser();

		$output = $this->renderPage($page_id, $theme_name, 'admin_default', editable: true);

		$this->assertStringContainsString(
			'<section id="edit-marker">Edit smoke</section>',
			$output,
			"Widget content not found for theme {$theme_name}",
		);
		$this->assertStringContainsString(
			'id="widget-' . $connection_id . '"',
			$output,
			"Widget connection wrapper not found for theme {$theme_name}",
		);
		$this->assertStringContainsString(
			'class="widget-edit"',
			$output,
			"Widget edit chrome not found for theme {$theme_name}",
		);
		$this->assertStringContainsString(
			'class="widget-insert',
			$output,
			"Widget inserter not found for theme {$theme_name}",
		);
		$this->assertGreaterThanOrEqual(
			2,
			substr_count($output, 'class="widget-insert'),
			"Expected at least 2 widget inserters for theme {$theme_name}",
		);
	}

	public function testSoAdminLogoutLinkDoesNotDoubleEscapeOrReuseBrokenLogoutReferer(): void
	{
		$page_id = self::FIXTURE_PAGE_ID;
		$this->insertPlainHtmlConnection($page_id, '<section id="smoke-content">Content</section>');
		$this->setRequestContext([
			'REQUEST_URI' => '/admin/?context=user&amp;event=logout&amp;referer=http%3A%2F%2Flocalhost%2Fadmin%2F',
		]);
		$this->impersonateDevOnlyUser();

		$output = $this->renderPage($page_id, 'SoAdmin', 'admin_default');

		$this->assertStringNotContainsString(
			'href="http://localhost/admin/?context=user&amp;amp',
			$output
		);
		$this->assertStringContainsString(
			'href="http://localhost/admin/?context=user&amp;event=logout&amp;referer=http%3A%2F%2Flocalhost%2Fadmin%2F"',
			$output
		);
		$this->assertStringContainsString(
			'href="http://localhost/admin/?context=Page&amp;event=EditmodeSwitch&amp;referer=http%3A%2F%2Flocalhost%2Fadmin%2F&amp;set=0"',
			$output
		);
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	private function impersonate(?string $username): void
	{
		$ctx = RequestContextHolder::current();

		if ($username === null) {
			$ctx->currentUser = null;
			$ctx->userSessionInitialized = true;
			Cache::flush(Roles::class);
			Cache::flush(User::class);

			return;
		}

		$user = EntityUser::findFirst(['username' => $username]);
		$this->assertNotNull($user, "Missing test fixture user: {$username}");

		$ctx->currentUser = $user->data();
		$ctx->userSessionInitialized = true;
		Cache::flush(Roles::class);
		Cache::flush(User::class);
	}

	/**
	 * Create a transaction-scoped user with only ROLE_SYSTEM_DEVELOPER.
	 *
	 * Using admin_developer (which also has ROLE_SYSTEM_ADMINISTRATOR) is
	 * problematic because LayoutComponentTopMenuAdmin::buildTree() calls
	 * widget_url() for widgets that don't exist in the test DB, triggering
	 * auto-generation that fails. A developer-only user avoids those code paths.
	 */
	private function impersonateDevOnlyUser(): void
	{
		$username = 'test_dev_only_' . uniqid();
		$user_id = DbHelper::insertHelper('users', [
			'username'  => $username,
			'password'  => 'x',
			'is_active' => 1,
		]);

		$role = DbHelper::selectOne('roles_tree', ['role' => RoleList::ROLE_SYSTEM_DEVELOPER]);
		$this->assertNotNull($role, 'ROLE_SYSTEM_DEVELOPER not found in roles_tree');

		DbHelper::insertHelper('users_roles_mapping', [
			'user_id' => $user_id,
			'role_id' => $role['node_id'],
		]);

		$ctx = RequestContextHolder::current();
		$ctx->currentUser = DbHelper::selectOne('users', ['user_id' => $user_id]);
		$ctx->userSessionInitialized = true;
		Cache::flush(Roles::class);
		Cache::flush(User::class);
	}

	private function setRequestContext(array $server_overrides = []): void
	{
		$server = array_replace([
			'REQUEST_URI' => '/admin-dropdown-smoke.html',
			'HTTP_HOST'   => 'localhost',
			'SERVER_PORT' => '80',
			'SERVER_PROTOCOL' => 'HTTP/1.1',
			'HTTPS'       => '',
			'HTTP_ACCEPT' => 'text/html',
		], $server_overrides);

		RequestContextHolder::initializeRequest(server: $server);
		RequestContextHolder::disablePersistentCacheWrite();
		$_SERVER = array_replace($_SERVER, $server);
	}

	private function renderPage(
		int $page_id,
		string $theme_name,
		string $layout_type_name,
		bool $editable = false,
	): string {
		$composer = new AdminDropdownSmokeComposer($page_id, $theme_name, $layout_type_name, $editable);

		return (string) $composer;
	}

	private function insertPlainHtmlConnection(int $page_id, string $content, int $seq = 1): int
	{
		$connection_id = DbHelper::insertHelper('widget_connections', [
			'page_id'     => $page_id,
			'slot_name'   => 'content',
			'widget_name' => WidgetList::PLAINHTML,
			'seq'         => $seq,
		]);

		PlainHtml::saveSettings(['content' => $content], $connection_id);

		return $connection_id;
	}
}

final class AdminDropdownSmokeComposer extends WebpageView
{
	public function __construct(int $page_id, string $theme_name, string $layout_type_name, bool $editable)
	{
		$this->_id = $page_id;
		$this->_resourceData = [
			'node_id'       => $page_id,
			'render_mode'   => 'html',
			'title'         => 'Admin Dropdown Smoke Test',
			'description'   => '',
			'keywords'      => '',
			'robots_index'  => 0,
			'robots_follow' => 0,
			'lang_id'       => 'en',
			'node_type'     => 'webpage',
		];
		$this->_theme             = ThemeBase::factory($theme_name);
		$this->_layoutTypeOverride = $layout_type_name;
		$this->layoutType         = Layout::factory($layout_type_name);
		$this->_editMode          = $editable;
	}
}
