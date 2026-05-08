<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class TreeRenderingContractTest extends TestCase
{
	protected function setUp(): void
	{
		RequestContextHolder::initializeRequest();
	}

	public function testWidgetBuildTreeReturnsStructuredAccessDeniedStateWithoutCallingAuthorizedBuilder(): void
	{
		$widget = new TreeRenderingDeniedWidget();
		$composer = new TreeRenderingTestComposer();
		$connection = new WidgetConnection([
			'connection_id' => 123,
			'widget_name' => WidgetList::PLAINHTML,
		]);

		$tree = $widget->buildTree($composer, $connection);

		$this->assertSame('statusMessage', $tree['component']);
		$this->assertSame('warning', $tree['props']['severity'] ?? null);
		$this->assertStringContainsString('Denied by test gate.', $tree['props']['message'] ?? '');
	}

	public function testHtmlTreeRendererRendersNestedSlotsAndRegistersChildAssetsBeforeBodyContent(): void
	{
		$composer = new TreeRenderingTestComposer('RadaptorPortalAdmin', 'widget_previewer');
		$renderer = new HtmlTreeRenderer(theme: $composer->getTheme());

		$output = $renderer->render([
			'component' => 'layout_widget_previewer',
			'props' => [],
			'slots' => [
				'content' => [[
					'component' => 'templateEngineDemoWrapper',
					'props' => [
						'engineName' => 'PHP',
						'engineClass' => 'TemplateRendererPhp',
						'fileExtension' => '.php',
						'sourceCode' => '<?php echo "demo";',
					],
					'slots' => [
						'demo' => [[
							'component' => 'statusMessage',
							'props' => [
								'severity' => 'info',
								'message' => 'Nested demo output',
							],
							'slots' => [],
						]],
					],
				]],
			],
		]);

		$this->assertStringContainsString('Nested demo output', $output);
		$this->assertStringContainsString('prism-tomorrow.min.css', $output);

		$asset_position = strpos($output, 'prism-tomorrow.min.css');
		$content_position = strpos($output, 'Template Source');

		$this->assertNotFalse($asset_position);
		$this->assertNotFalse($content_position);
		$this->assertLessThan($content_position, $asset_position);
	}

	public function testWidgetPreviewBuildsMockSubtreeAndOverridesLayout(): void
	{
		RequestContextHolder::initializeRequest(get: [
			'widget' => 'TemplateEngineDemoPhp',
			'theme' => 'RadaptorPortalAdmin',
		]);

		$composer = new TreeRenderingTestComposer('RadaptorPortalAdmin', 'admin_default');
		$connection = new WidgetConnection([
			'connection_id' => 456,
			'widget_name' => 'WidgetPreview',
		]);
		$widget = new WidgetWidgetPreview();

		$tree = $widget->buildTree($composer, $connection);

		$this->assertContains($tree['component'] ?? '', ['widgetPreviewInfo', 'statusMessage']);
		$this->assertSame('widget_previewer', $composer->getLayoutTypeName());

		if (($tree['component'] ?? null) === 'widgetPreviewInfo') {
			$this->assertStringContainsString('output_channel=sdui_json', (string) ($tree['props']['jsonPreviewUrl'] ?? ''));
			$this->assertContains('RadaptorPortalAdmin', $tree['props']['themesWithWidgetTemplate'] ?? []);
			$this->assertArrayHasKey('preview', $tree['slots']);
			$this->assertCount(1, $tree['slots']['preview']);
			$this->assertSame('templateEngineDemoWrapper', $tree['slots']['preview'][0]['component']);
			$this->assertTrue((bool) ($tree['slots']['preview'][0]['meta']['render_flags']['is_mock'] ?? false));

			return;
		}

		$this->assertContains($tree['props']['severity'] ?? null, ['warning', 'error']);
		$this->assertNotSame('', trim((string) ($tree['props']['message'] ?? '')));
	}

	public function testWidgetPreviewListBuildsTemplateSupportFromResolvedRootTemplate(): void
	{
		$composer = new TreeRenderingTestComposer('RadaptorPortalAdmin', 'admin_default');
		$connection = new WidgetConnection([
			'connection_id' => 457,
			'widget_name' => 'WidgetPreview',
		]);
		$widget = new WidgetWidgetPreview();

		$tree = $widget->buildTree($composer, $connection);

		$this->assertSame('widgetPreviewList', $tree['component']);
		$this->assertArrayHasKey('allThemes', $tree['props']);
		$this->assertArrayHasKey('widgets', $tree['props']);
		$this->assertStringContainsString('resolved preview HTML template', (string)($tree['props']['templateScopeNote'] ?? ''));
	}

	public function testFormWidgetPreviewUsesMainFormTemplateForThemeSupport(): void
	{
		RequestContextHolder::initializeRequest(get: [
			'widget' => 'Form',
			'theme' => 'RadaptorPortalAdmin',
		]);

		$composer = new TreeRenderingTestComposer('RadaptorPortalAdmin', 'admin_default');
		$connection = new WidgetConnection([
			'connection_id' => 458,
			'widget_name' => 'WidgetPreview',
		]);
		$widget = new WidgetWidgetPreview();

		$tree = $widget->buildTree($composer, $connection);

		$this->assertSame('sdui.form', $tree['props']['templateName'] ?? null);
		$this->assertContains('RadaptorPortalAdmin', $tree['props']['themesWithWidgetTemplate'] ?? []);
		$this->assertContains('RadaptorPortalAdmin', $tree['props']['allThemes'] ?? []);
	}

	public function testWidgetFormBuildMockTreeReturnsStructuredPreviewForm(): void
	{
		$composer = new TreeRenderingTestComposer();
		$connection = new WidgetConnection([
			'connection_id' => 789,
			'widget_name' => 'Form',
		]);
		$widget = new WidgetForm();

		$tree = $widget->buildMockTree($composer, $connection);
		$components = $this->collectComponentNames($tree);

		$this->assertSame('form', $tree['component']);
		$this->assertSame('Form preview demo', $tree['props']['title'] ?? null);
		$this->assertCount(1, $tree['slots']['hidden_fields'] ?? []);
		$this->assertCount(5, $tree['slots']['rows'] ?? []);
		$this->assertContains('form.input.text', $components);
		$this->assertContains('form.input.password', $components);
		$this->assertContains('form.input.select', $components);
		$this->assertContains('form.input.date', $components);
		$this->assertContains('form.input.datetime', $components);
		$this->assertContains('form.input.checkbox', $components);
		$this->assertContains('form.input.checkboxgroup', $components);
		$this->assertContains('form.input.radiogroup', $components);
		$this->assertContains('form.input.textarea', $components);
	}

	public function testWebpageViewDefaultsToHtmlOutputChannelForHtmlAcceptHeader(): void
	{
		RequestContextHolder::initializeRequest(server: [
			'HTTP_ACCEPT' => 'text/html',
		]);

		$composer = new TreeRenderingTestComposer();

		$this->assertSame(WebpageView::OUTPUT_CHANNEL_HTML, $composer->getOutputChannel());
	}

	public function testWebpageViewUsesJsonOutputChannelForJsonAcceptHeader(): void
	{
		RequestContextHolder::initializeRequest(server: [
			'HTTP_ACCEPT' => 'application/json',
		]);

		$composer = new TreeRenderingTestComposer();

		$this->assertSame(WebpageView::OUTPUT_CHANNEL_SDUI_JSON, $composer->getOutputChannel());
	}

	public function testWebpageViewOutputChannelQueryParamOverridesAcceptHeader(): void
	{
		RequestContextHolder::initializeRequest(
			get: [
				'output_channel' => WebpageView::OUTPUT_CHANNEL_SDUI_JSON,
			],
			server: [
				'HTTP_ACCEPT' => 'text/html',
			],
		);

		$composer = new TreeRenderingTestComposer();

		$this->assertSame(WebpageView::OUTPUT_CHANNEL_SDUI_JSON, $composer->getOutputChannel());
	}

	public function testSduiJsonSerializerRejectsRawHtmlNodes(): void
	{
		$serializer = new SduiJsonSerializer();

		$this->expectException(LogicException::class);
		$serializer->serializeDocument([
			'type' => 'sub',
			'component' => '_rawHtml',
			'props' => [
				'html' => '<div>legacy</div>',
			],
			'slots' => [],
		], 'en-US');
	}

	public function testAbstractFormBuildTreeReturnsStructuredFormNodesWithResolvedStrings(): void
	{
		$composer = new TreeRenderingTestComposer();
		$form = new TreeRenderingExampleForm('tree_rendering_example', 'example', $composer);

		$tree = $form->buildTree();

		$this->assertSame('form', $tree['component']);
		$this->assertSame(AbstractForm::_MODE_CREATE, $tree['props']['mode'] ?? null);
		$this->assertSame('Example Form', $tree['props']['title'] ?? null);
		$this->assertSame('fexample_input_1', $tree['props']['field_refs']['username']['id'] ?? null);
		$this->assertSame('row_fexample_input_1', $tree['props']['field_refs']['username']['row_id'] ?? null);
		$this->assertCount(1, $tree['slots']['hidden_fields'] ?? []);
		$this->assertCount(1, $tree['slots']['rows'] ?? []);
		$this->assertSame('form.input.text', $tree['slots']['rows'][0]['slots']['content'][0]['component'] ?? null);
		$this->assertSame('Username', $tree['slots']['rows'][0]['slots']['content'][0]['props']['label'] ?? null);
		$this->assertSame('NotEmpty', $tree['slots']['rows'][0]['slots']['content'][0]['props']['validators'][0]['validator'] ?? null);
		$this->assertSame('form.input.hidden', $tree['slots']['hidden_fields'][0]['component'] ?? null);
	}

	public function testHtmlComponentTemplateResolverUsesSduiFormTemplatesWithoutLegacyFallback(): void
	{
		TestHelperEnvironment::setEnvironmentVariable('RADAPTOR_WORKSPACE_DEV_MODE', '1');
		TestHelperEnvironment::setEnvironmentVariable('RADAPTOR_DEV_ROOT', '/workspace');
		PackageLocalOverrideHelper::reset();

		try {
			$this->assertSame('sdui.form', HtmlComponentTemplateResolver::resolveTemplateName([
				'component' => 'form',
				'props' => [
					'form_name' => 'blog',
				],
			]));

			$this->assertSame('sdui.form', HtmlComponentTemplateResolver::resolveTemplateName([
				'component' => 'form',
				'props' => [
					'form_name' => 'tree_rendering_example',
				],
			]));
		} finally {
			PackageLocalOverrideHelper::reset();
			TestHelperEnvironment::revertEnvironmentVariable('RADAPTOR_DEV_ROOT');
			TestHelperEnvironment::revertEnvironmentVariable('RADAPTOR_WORKSPACE_DEV_MODE');
		}
	}

	public function testResourceTreeBuildsResolvedUiPayload(): void
	{
		$composer = new TreeRenderingTestComposer('RadaptorPortalAdmin', 'admin_default');
		$connection = new WidgetConnection([
			'connection_id' => 789,
			'widget_name' => WidgetList::RESOURCETREE,
		]);
		$widget = new WidgetResourceTree();

		$tree = $widget->buildTree($composer, $connection);

		$this->assertNotSame('', $tree['component']);
		$this->assertSame('jstree_resources_789', $tree['props']['jstree_id'] ?? null);
		$this->assertSame(
			JsTreeApiService::buildResourcesStrings()['cms.resource_browser.site_structure'],
			$tree['strings']['cms.resource_browser.site_structure'] ?? null
		);
		$this->assertNotEmpty($tree['strings']['common.delete'] ?? null);
	}

	public function testRoleListBuildsResolvedUiPayload(): void
	{
		$composer = new TreeRenderingTestComposer('RadaptorPortalAdmin', 'admin_default');
		$connection = new WidgetConnection([
			'connection_id' => 790,
			'widget_name' => WidgetList::ROLELIST,
		]);
		$widget = new TreeRenderingRoleListWidget();

		$tree = $widget->buildTree($composer, $connection);

		$this->assertSame('jstree_790', $tree['props']['jstree_id'] ?? null);
		$this->assertSame(
			JsTreeApiService::buildRolesStrings()['user.usergroup.roles'],
			$tree['strings']['user.usergroup.roles'] ?? null
		);
		$this->assertNotEmpty($tree['strings']['selection.delete_selected'] ?? null);
	}

	public function testUsergroupListBuildsResolvedUiPayload(): void
	{
		$composer = new TreeRenderingTestComposer('RadaptorPortalAdmin', 'admin_default');
		$connection = new WidgetConnection([
			'connection_id' => 791,
			'widget_name' => WidgetList::USERGROUPLIST,
		]);
		$widget = new TreeRenderingUsergroupListWidget();

		$tree = $widget->buildTree($composer, $connection);

		$this->assertSame('jstree_791', $tree['props']['jstree_id'] ?? null);
		$this->assertSame(
			JsTreeApiService::buildUsergroupsStrings()['user.usergroup.all'],
			$tree['strings']['user.usergroup.all'] ?? null
		);
		$this->assertNotEmpty($tree['strings']['user.usergroup.roles'] ?? null);
	}

	public function testAdminMenuBuildsResolvedUiPayload(): void
	{
		$composer = new TreeRenderingTestComposer('RadaptorPortalAdmin', 'admin_default');
		$connection = new WidgetConnection([
			'connection_id' => 792,
			'widget_name' => WidgetList::ADMINMENU,
		]);
		$widget = new WidgetAdminMenu();

		$tree = $widget->buildTree($composer, $connection);

		$this->assertSame('jstree_adminmenu_792', $tree['props']['jstree_id'] ?? null);
		$this->assertSame(
			JsTreeApiService::buildAdminMenuStrings()['admin.menu.admin_menu'],
			$tree['strings']['admin.menu.admin_menu'] ?? null
		);
		$this->assertNotEmpty($tree['strings']['cms.menu.new_item'] ?? null);
	}

	public function testHtmlTreeRendererRendersRoleSelectorWithResolvedUiProps(): void
	{
		$composer = new TreeRenderingTestComposer('RadaptorPortalAdmin', 'admin_default');
		$renderer = new HtmlTreeRenderer(theme: $composer->getTheme());
		$strings = JsTreeApiService::buildRoleSelectorStrings();

		$output = $renderer->render([
			'component' => 'jsTree.roleSelector',
			'props' => [
				'jstree_type' => 'roleSelector',
				'selectorType' => 'user',
				'selectorId' => 1,
				'title' => 'admin_developer',
			],
			'slots' => [],
			'strings' => $strings,
		]);

		$this->assertStringContainsString($strings['user.role_selector.title'], $output);
		$this->assertStringContainsString($strings['user.role_selector.help'], $output);
		$this->assertStringContainsString($strings['user.role_selector.available_roles'], $output);
	}

	public function testHtmlTreeRendererRendersUsergroupSelectorWithResolvedUiProps(): void
	{
		$composer = new TreeRenderingTestComposer('RadaptorPortalAdmin', 'admin_default');
		$renderer = new HtmlTreeRenderer(theme: $composer->getTheme());
		$strings = JsTreeApiService::buildUsergroupSelectorStrings();

		$output = $renderer->render([
			'component' => 'jsTree.usergroupSelector',
			'props' => [
				'jstree_id' => 'jstree_999',
				'jstree_type' => 'usergroupSelector',
				'selectorId' => 1,
				'title' => 'admin_developer',
			],
			'slots' => [],
			'strings' => $strings,
		]);

		$this->assertStringContainsString($strings['user.usergroup_selector.title'], $output);
		$this->assertStringContainsString($strings['user.usergroup_selector.help'], $output);
		$this->assertStringContainsString($strings['user.usergroup_selector.available_usergroups'], $output);
	}

	public function testHtmlTreeRendererRendersResourceAclSelectorWithResolvedStrings(): void
	{
		$composer = new TreeRenderingTestComposer('RadaptorPortalAdmin', 'admin_default');
		$renderer = new HtmlTreeRenderer(theme: $composer->getTheme());
		$strings = WidgetResourceAclSelector::buildStrings();

		$output = $renderer->render([
			'component' => 'resourceAclSelector',
			'props' => [
				'selectorId' => 1,
				'title' => '/admin/index.html',
				'is_inheriting_acl' => true,
			],
			'slots' => [],
			'strings' => $strings,
		]);

		$this->assertStringContainsString($strings['cms.resource_acl.title'], $output);
		$this->assertStringContainsString($strings['cms.resource_acl.inherit_label'], $output);
		$this->assertStringContainsString($strings['cms.resource_acl.specific_title'], $output);
		$this->assertStringContainsString($strings['cms.resource_acl.assign'], $output);
	}

	public function testSideMenuAdminBuildTreeProvidesResolvedStrings(): void
	{
		$composer = new TreeRenderingTestComposer('RadaptorPortalAdmin', 'admin_default');
		$component = new LayoutComponentSideMenuAdmin($composer);

		$tree = $component->buildTree();

		$this->assertSame('sideMenuAdmin', $tree['component']);
		$this->assertSame(
			LayoutComponentSideMenuAdmin::buildStrings()['admin.menu.widget_preview'],
			$tree['strings']['admin.menu.widget_preview'] ?? null
		);
		$this->assertSame(
			LayoutComponentSideMenuAdmin::buildStrings()['admin.menu.section.configuration'],
			$tree['strings']['admin.menu.section.configuration'] ?? null
		);
	}

	public function testWidgetEditTreeProvidesResolvedEditBarStrings(): void
	{
		$composer = new TreeRenderingTestComposer('RadaptorPortalAdmin', 'admin_default');
		$connection = new WidgetConnection([
			'connection_id' => 321,
			'widget_name' => WidgetList::PLAINHTML,
		]);

		$tree = Widget::buildEditTree($composer, $connection, [
			'type' => SduiNode::TYPE_WIDGET,
			'component' => 'statusMessage',
			'props' => [
				'severity' => 'info',
				'message' => 'Preview',
			],
			'slots' => [],
		]);

		$editBarTree = $tree['slots']['edit_bar'][0] ?? [];

		$this->assertSame('editBar.common', $editBarTree['component'] ?? null);
		$this->assertSame(t('common.move_up'), $editBarTree['strings']['common.move_up'] ?? null);
		$this->assertSame(
			t('cms.widget_connection.remove_from_webpage'),
			$editBarTree['strings']['cms.widget_connection.remove_from_webpage'] ?? null
		);
	}

	public function testUserListBuildsResolvedStrings(): void
	{
		$composer = new TreeRenderingTestComposer('RadaptorPortalAdmin', 'admin_default');
		$connection = new WidgetConnection([
			'connection_id' => 655,
			'widget_name' => WidgetList::USERLIST,
		]);
		$widget = new TreeRenderingUserListWidget();

		$tree = $widget->buildTree($composer, $connection);

		$this->assertSame('userList', $tree['component']);
		$this->assertSame(
			WidgetUserList::buildStrings()['user.list.title'],
			$tree['strings']['user.list.title'] ?? null
		);
		$this->assertSame(
			WidgetUserList::buildStrings()['user.action.usergroups'],
			$tree['strings']['user.action.usergroups'] ?? null
		);
	}

	public function testUserDescriptionBuildsResolvedStrings(): void
	{
		RequestContextHolder::initializeRequest(get: [
			'id' => 1,
		]);

		$composer = new TreeRenderingTestComposer('RadaptorPortalAdmin', 'admin_default');
		$connection = new WidgetConnection([
			'connection_id' => 656,
			'widget_name' => WidgetList::USERDESCRIPTION,
		]);
		$widget = new WidgetUserDescription();

		$tree = $widget->buildTree($composer, $connection);

		$this->assertSame('userDescription', $tree['component']);
		$this->assertSame(
			WidgetUserDescription::buildStrings()['user.description.title'],
			$tree['strings']['user.description.title'] ?? null
		);
		$this->assertNotEmpty($tree['props']['userData']['username'] ?? null);
	}

	public function testWidgetInserterBuildTreeProvidesResolvedStrings(): void
	{
		$composer = new TreeRenderingTestComposer('RadaptorPortalAdmin', 'admin_default');

		$tree = $composer->buildWidgetInserterTree('content');
		$addFromListTree = $tree['slots']['add_widget_from_list'][0] ?? [];

		$this->assertSame('widgetInsert', $tree['component']);
		// @phpstan-ignore-next-line
		$this->assertSame(t('cms.widget.insert_from_clipboard'), $tree['strings']['cms.widget.insert_from_clipboard'] ?? null);
		$this->assertSame('addWidgetFromList', $addFromListTree['component'] ?? null);
		// @phpstan-ignore-next-line
		$this->assertSame(t('cms.widget.insert.button'), $addFromListTree['strings']['cms.widget.insert.button'] ?? null);
	}

	public function testUserMenuBuildTreeProvidesResolvedStrings(): void
	{
		$composer = new TreeRenderingTestComposer('RadaptorPortalAdmin', 'admin_default');
		$component = new LayoutComponentUserMenu($composer);

		$tree = $component->buildTree();

		$this->assertSame('userMenu', $tree['component']);
		$this->assertSame(t('common.logout'), $tree['strings']['common.logout'] ?? null);
	}

	// Removed: testDisqusBuildTreeProvidesResolvedStrings — LayoutComponentDisqus was deleted with the Social module

	/**
	 * @param array<string, mixed> $node
	 * @return list<string>
	 */
	private function collectComponentNames(array $node): array
	{
		$components = [];
		$component = (string)($node['component'] ?? '');

		if ($component !== '') {
			$components[] = $component;
		}

		foreach (($node['slots'] ?? []) as $items) {
			if (!is_array($items)) {
				continue;
			}

			foreach ($items as $item) {
				if (!is_array($item)) {
					continue;
				}

				$components = array_merge($components, $this->collectComponentNames($item));
			}
		}

		return $components;
	}
}

final class TreeRenderingTestComposer extends WebpageView
{
	public function __construct(string $theme_name = 'RadaptorPortalAdmin', string $layout_type_name = 'widget_previewer')
	{
		if (!Themes::checkThemeDataExists($theme_name)) {
			throw new \PHPUnit\Framework\SkippedWithMessageException("Theme '{$theme_name}' is not available in this runtime.");
		}

		$layout_classname = Layout::getLayoutClassName($layout_type_name);

		if (!class_exists($layout_classname)) {
			throw new \PHPUnit\Framework\SkippedWithMessageException("Layout '{$layout_type_name}' is not available in this runtime.");
		}

		$this->_id = 1;
		$this->_resourceData = [
			'render_mode' => 'html',
			'title' => 'Tree Rendering Test',
			'description' => '',
			'keywords' => '',
			'robots_index' => 0,
			'robots_follow' => 0,
			'lang_id' => 'en',
			'node_type' => 'webpage',
		];
		$this->_theme = ThemeBase::factory($theme_name);
		$this->_layoutTypeOverride = $layout_type_name;
		$this->layoutType = Layout::factory($layout_type_name);
	}
}

final class TreeRenderingDeniedWidget extends AbstractWidget
{
	public static function getName(): string
	{
		return 'Denied Widget';
	}

	public static function getDescription(): string
	{
		return 'Denied Widget';
	}

	public static function getListVisibility(): bool
	{
		return false;
	}

	public function getAccessDeniedMessage(): string
	{
		return 'Denied by test gate.';
	}

	public function canAccess(iTreeBuildContext $tree_build_context, WidgetConnection $connection): bool
	{
		return false;
	}

	protected function buildAuthorizedTree(iTreeBuildContext $tree_build_context, WidgetConnection $connection, array $build_context = []): array
	{
		throw new LogicException('buildAuthorizedTree() must not run when canAccess() denies the widget.');
	}
}

final class TreeRenderingRoleListWidget extends WidgetRoleList
{
	public function canAccess(iTreeBuildContext $tree_build_context, WidgetConnection $connection): bool
	{
		return true;
	}
}

final class TreeRenderingUsergroupListWidget extends WidgetUsergroupList
{
	public function canAccess(iTreeBuildContext $tree_build_context, WidgetConnection $connection): bool
	{
		return true;
	}
}

final class TreeRenderingUserListWidget extends WidgetUserList
{
	public function canAccess(iTreeBuildContext $tree_build_context, WidgetConnection $connection): bool
	{
		return true;
	}
}

final class TreeRenderingExampleForm extends AbstractForm
{
	public static function getName(): string
	{
		return 'Tree Rendering Example Form';
	}

	public static function getDescription(): string
	{
		return 'Tree Rendering Example Form';
	}

	public static function getListVisibility(): bool
	{
		return false;
	}

	public static function getDefaultPathForCreation(): array
	{
		return [];
	}

	public function hasRole(): bool
	{
		return true;
	}

	public function commit(): void
	{
	}

	public function setMetadata(): void
	{
		$this->_meta->title = 'Example Form';
		$this->_meta->sub_title = 'Structured tree test';
		$this->_meta->enableAutoReferer = false;
	}

	public function makeInputs(): void
	{
		$username = new FormInputText('username', $this);
		$username->label = 'Username';
		$validator = $username->addValidator(new FormValidatorNotEmpty('Required'));
		assert($validator instanceof FormValidatorNotEmpty);

		$token = new FormInputHidden('csrf_token', $this);
		$token->setValue('abc123');
	}
}
