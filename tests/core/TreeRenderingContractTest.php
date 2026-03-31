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

		$this->assertSame('widgetPreviewInfo', $tree['component']);
		$this->assertSame('widget_previewer', $composer->getLayoutTypeName());
		$this->assertStringContainsString('output_channel=sdui_json', (string)($tree['props']['jsonPreviewUrl'] ?? ''));
		$this->assertContains('RadaptorPortalAdmin', $tree['props']['themesWithWidgetTemplate'] ?? []);
		$this->assertContains('Tracker', $tree['props']['allThemes'] ?? []);
		$this->assertNotContains('SoAdmin', $tree['props']['themesWithWidgetTemplate'] ?? []);
		$this->assertNotContains('SoAdmin', $tree['props']['allThemes'] ?? []);
		$this->assertArrayHasKey('preview', $tree['slots']);
		$this->assertCount(1, $tree['slots']['preview']);
		$this->assertSame('templateEngineDemoWrapper', $tree['slots']['preview'][0]['component']);
		$this->assertTrue((bool)($tree['slots']['preview'][0]['meta']['render_flags']['is_mock'] ?? false));
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
		$this->assertSame(['RadaptorPortalAdmin'], $tree['props']['themesWithWidgetTemplate'] ?? null);
		$this->assertSame(['RadaptorPortalAdmin', 'Tracker'], $tree['props']['allThemes'] ?? null);
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
		], 'en_US');
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
		$this->assertSame('sdui.form.blog', HtmlComponentTemplateResolver::resolveTemplateName([
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

	public function testBlogListBuildsResolvedStrings(): void
	{
		$composer = new TreeRenderingTestComposer('RadaptorPortalAdmin', 'admin_default');
		$connection = new WidgetConnection([
			'connection_id' => 654,
			'widget_name' => WidgetList::BLOGLIST,
		]);
		$widget = new TreeRenderingBlogListWidget();

		$tree = $widget->buildTree($composer, $connection);

		$this->assertSame('blogList', $tree['component']);
		$this->assertSame(
			WidgetBlogList::buildStrings()['blog.list.title'],
			$tree['strings']['blog.list.title'] ?? null
		);
		$this->assertSame(
			WidgetBlogList::buildStrings()['datatable.zero_records'],
			$tree['strings']['datatable.zero_records'] ?? null
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

	public function testPublicUserListBuildsResolvedStrings(): void
	{
		$composer = new TreeRenderingTestComposer('Tracker', 'public_default');
		$connection = new WidgetConnection([
			'connection_id' => 657,
			'widget_name' => WidgetList::PUBLICUSERLIST,
		]);
		$widget = new TreeRenderingPublicUserListWidget();

		$tree = $widget->buildTree($composer, $connection);

		$this->assertSame('publicUserList', $tree['component']);
		$this->assertSame(
			WidgetUserList::buildStrings()['user.list.title'],
			$tree['strings']['user.list.title'] ?? null
		);
		$this->assertSame(
			WidgetUserList::buildStrings()['user.action.datasheet'],
			$tree['strings']['user.action.datasheet'] ?? null
		);
	}

	public function testPublicUserDescriptionBuildsResolvedStrings(): void
	{
		RequestContextHolder::initializeRequest(get: [
			'id' => 1,
		]);

		$composer = new TreeRenderingTestComposer('Tracker', 'public_default');
		$connection = new WidgetConnection([
			'connection_id' => 658,
			'widget_name' => WidgetList::PUBLICUSERDESCRIPTION,
		]);
		$widget = new WidgetPublicUserDescription();

		$tree = $widget->buildTree($composer, $connection);

		$this->assertSame('publicUserDescription', $tree['component']);
		$this->assertSame(
			WidgetUserDescription::buildStrings()['user.description.title'],
			$tree['strings']['user.description.title'] ?? null
		);
		$this->assertNotEmpty($tree['props']['userData']['username'] ?? null);
	}

	public function testTopMenuBuildTreeProvidesResolvedStrings(): void
	{
		$composer = new TreeRenderingTestComposer('Tracker', 'public_2row');
		$component = new LayoutComponentTopMenu($composer);

		$tree = $component->buildTree();

		$this->assertSame('topMenu', $tree['component']);
		$this->assertSame(
			LayoutComponentTopMenu::buildStrings()['admin.menu.section.administration'],
			$tree['strings']['admin.menu.section.administration'] ?? null
		);
		$this->assertSame(
			LayoutComponentTopMenu::buildStrings()['widget.ticket_list.name'],
			$tree['strings']['widget.ticket_list.name'] ?? null
		);
	}

	public function testMainMenuBuildsResolvedStrings(): void
	{
		$composer = new TreeRenderingTestComposer('Tracker', 'admin_default');
		$connection = new WidgetConnection([
			'connection_id' => 659,
			'widget_name' => WidgetList::MAINMENU,
		]);
		$widget = new WidgetMainMenu();

		$tree = $widget->buildTree($composer, $connection);

		$this->assertSame('jsTree.mainMenu', $tree['component']);
		$this->assertSame(
			JsTreeApiService::buildMainMenuStrings()['cms.menu.root'],
			$tree['strings']['cms.menu.root'] ?? null
		);
		$this->assertSame(
			JsTreeApiService::buildMainMenuStrings()['selection.invalid_entry'],
			$tree['strings']['selection.invalid_entry'] ?? null
		);
	}

	public function testHeaderRightBuildTreeProvidesResolvedStrings(): void
	{
		$composer = new TreeRenderingTestComposer('Tracker', 'public_2row');
		$component = new LayoutComponentHeaderRight($composer);

		$tree = $component->buildTree();

		$this->assertSame('headerRight', $tree['component']);
		$this->assertSame(
			LayoutComponentHeaderRight::buildStrings()['admin.menu.home'],
			$tree['strings']['admin.menu.home'] ?? null
		);
	}

	public function testPublic2rowLayoutBuildTreeProvidesResolvedStrings(): void
	{
		$composer = new TreeRenderingTestComposer('Tracker', 'public_2row');
		$layout = new LayoutTypePublic2row();

		$tree = $layout->buildTree($composer, [
			'content' => [],
			'narrow' => [],
		]);

		$this->assertSame('layout_public_2row', $tree['component']);
		$this->assertSame(
			LayoutTypePublic2row::buildStrings()['layout.public_2row.hero.self_discovery'],
			$tree['strings']['layout.public_2row.hero.self_discovery'] ?? null
		);
		$this->assertSame(
			LayoutTypePublic2row::buildStrings()['layout.public_2row.hero.solution'],
			$tree['strings']['layout.public_2row.hero.solution'] ?? null
		);
	}

	public function testTicketListBuildsResolvedStrings(): void
	{
		$composer = new TreeRenderingTestComposer('Tracker', 'public_default');
		$connection = new WidgetConnection([
			'connection_id' => 660,
			'widget_name' => WidgetList::TICKETLIST,
		]);
		$widget = new WidgetTicketList();

		$tree = $widget->buildTree($composer, $connection);

		$this->assertSame('ticketList', $tree['component']);
		$this->assertSame(
			WidgetTicketList::buildStrings()['ticket.widget.list.name'],
			$tree['strings']['ticket.widget.list.name'] ?? null
		);
		$this->assertSame(
			WidgetTicketList::buildStrings()['datatable.zero_records'],
			$tree['strings']['datatable.zero_records'] ?? null
		);
	}

	public function testTicketDescriptionBuildsResolvedStrings(): void
	{
		$composer = new TreeRenderingTestComposer('Tracker', 'public_default');
		$renderer = new HtmlTreeRenderer(theme: $composer->getTheme());
		$strings = WidgetTicketDescription::buildStrings(42);

		$output = $renderer->render([
			'component' => 'ticketDescription',
			'props' => [
				'ticketData' => [
					'id' => 42,
					'state' => 'Open',
					'title' => '',
					'assigned_user_id' => 0,
					'contactperson' => 'John Doe',
					'project_name' => 'Example Project',
					'start_date' => '2026-03-14',
					'end_date' => '2026-03-15',
					'type' => 'Bug',
					'priority' => 'High',
					'tags' => '',
					'description' => 'Synthetic ticket description',
				],
				'modificationsList' => [],
			],
			'slots' => [
				'history' => [],
			],
			'strings' => $strings,
		]);

		$this->assertStringContainsString($strings['ticket.description.heading'], $output);
		$this->assertStringContainsString($strings['ticket.field.assignee.label'], $output);
		$this->assertStringContainsString($strings['ticket.field.description.label'], $output);
		$this->assertStringContainsString($strings['ticket.description.no_assignee'], $output);
		$this->assertStringContainsString($strings['common.no_data'], $output);
	}

	public function testTicketStateListBuildsResolvedStrings(): void
	{
		$composer = new TreeRenderingTestComposer('Tracker', 'public_default');
		$connection = new WidgetConnection([
			'connection_id' => 662,
			'widget_name' => WidgetList::TICKETSTATELIST,
		]);
		$widget = new WidgetTicketStateList();

		$tree = $widget->buildTree($composer, $connection);

		$this->assertSame('ticketStateList', $tree['component']);
		$this->assertSame(
			WidgetTicketStateList::buildStrings()['ticket.state.field.is_open.label'],
			$tree['strings']['ticket.state.field.is_open.label'] ?? null
		);
	}

	public function testTicketTypeListBuildsResolvedStrings(): void
	{
		$composer = new TreeRenderingTestComposer('Tracker', 'public_default');
		$connection = new WidgetConnection([
			'connection_id' => 663,
			'widget_name' => WidgetList::TICKETTYPELIST,
		]);
		$widget = new WidgetTicketTypeList();

		$tree = $widget->buildTree($composer, $connection);

		$this->assertSame('ticketTypeList', $tree['component']);
		$this->assertSame(
			WidgetTicketTypeList::buildStrings()['ticket.type.field.name.label'],
			$tree['strings']['ticket.type.field.name.label'] ?? null
		);
	}

	public function testTicketPriorityListBuildsResolvedStrings(): void
	{
		$composer = new TreeRenderingTestComposer('Tracker', 'public_default');
		$connection = new WidgetConnection([
			'connection_id' => 664,
			'widget_name' => WidgetList::TICKETPRIORITYLIST,
		]);
		$widget = new WidgetTicketPriorityList();

		$tree = $widget->buildTree($composer, $connection);

		$this->assertSame('ticketPriorityList', $tree['component']);
		$this->assertSame(
			WidgetTicketPriorityList::buildStrings()['ticket.priority.field.seq.label'],
			$tree['strings']['ticket.priority.field.seq.label'] ?? null
		);
	}

	public function testProjectListBuildsResolvedStrings(): void
	{
		$composer = new TreeRenderingTestComposer('Tracker', 'public_default');
		$connection = new WidgetConnection([
			'connection_id' => 665,
			'widget_name' => WidgetList::PROJECTLIST,
		]);
		$widget = new WidgetProjectList();

		$tree = $widget->buildTree($composer, $connection);

		$this->assertSame('projectList', $tree['component']);
		$this->assertSame(
			WidgetProjectList::buildStrings()['project.list.title'],
			$tree['strings']['project.list.title'] ?? null
		);
		$this->assertSame(
			WidgetProjectList::buildStrings()['record_action.versions'],
			$tree['strings']['record_action.versions'] ?? null
		);
	}

	public function testProjectStateListBuildsResolvedStrings(): void
	{
		$composer = new TreeRenderingTestComposer('Tracker', 'public_default');
		$connection = new WidgetConnection([
			'connection_id' => 666,
			'widget_name' => WidgetList::PROJECTSTATELIST,
		]);
		$widget = new WidgetProjectStateList();

		$tree = $widget->buildTree($composer, $connection);

		$this->assertSame('projectStateList', $tree['component']);
		$this->assertSame(
			WidgetProjectStateList::buildStrings()['project.state.field.name.label'],
			$tree['strings']['project.state.field.name.label'] ?? null
		);
	}

	public function testTimeTrackerListBuildsResolvedStrings(): void
	{
		$composer = new TreeRenderingTestComposer('Tracker', 'public_default');
		$connection = new WidgetConnection([
			'connection_id' => 667,
			'widget_name' => WidgetList::TIMETRACKERLIST,
		]);
		$widget = new WidgetTimeTrackerList();

		$tree = $widget->buildTree($composer, $connection);

		$this->assertSame('TimeTrackerList', $tree['component']);
		$this->assertSame(
			WidgetTimeTrackerList::buildStrings()['timetracker.list.title'],
			$tree['strings']['timetracker.list.title'] ?? null
		);
		$this->assertSame(
			WidgetTimeTrackerList::buildStrings()['timetracker.delete_confirm'],
			$tree['strings']['timetracker.delete_confirm'] ?? null
		);
	}

	public function testTimeTrackerControlStoppedBuildTreeProvidesResolvedStrings(): void
	{
		$composer = new TreeRenderingTestComposer('Tracker', 'public_default');
		$component = new LayoutComponentTimeTrackerControl($composer);

		$tree = $component->buildTree();

		$this->assertSame('TimeTrackerControl.stopped', $tree['component']);
		$this->assertSame(
			LayoutComponentTimeTrackerControl::buildStrings()['timetracker.control.start'],
			$tree['strings']['timetracker.control.start'] ?? null
		);
	}

	public function testHtmlTreeRendererRendersTimeTrackerRunningWithResolvedStrings(): void
	{
		$composer = new TreeRenderingTestComposer('Tracker', 'public_default');
		$renderer = new HtmlTreeRenderer(theme: $composer->getTheme());
		$strings = LayoutComponentTimeTrackerControl::buildStrings();

		$output = $renderer->render([
			'component' => 'TimeTrackerControl.running',
			'props' => [
				'data' => [
					'description' => 'Investigating issue',
					'timetracker_start' => time() - 300,
				],
			],
			'slots' => [],
			'strings' => $strings,
		]);

		$this->assertStringContainsString($strings['timetracker.control.title'], $output);
		$this->assertStringContainsString($strings['timetracker.control.stop'], $output);
		$this->assertStringContainsString($strings['timetracker.field.ticket.label'], $output);
	}

	public function testTagListBuildsResolvedStrings(): void
	{
		$composer = new TreeRenderingTestComposer('Tracker', 'public_default');
		$connection = new WidgetConnection([
			'connection_id' => 668,
			'widget_name' => WidgetList::TAGLIST,
		]);
		$widget = new WidgetTagList();

		$tree = $widget->buildTree($composer, $connection);

		$this->assertSame('tagList', $tree['component']);
		$this->assertSame(
			WidgetTagList::buildStrings()['tags.list.title'],
			$tree['strings']['tags.list.title'] ?? null
		);
		$this->assertSame(
			WidgetTagList::buildStrings()['datatable.displayed_columns'],
			$tree['strings']['datatable.displayed_columns'] ?? null
		);
	}

	public function testCompanyListBuildsResolvedStrings(): void
	{
		$composer = new TreeRenderingTestComposer('Tracker', 'public_default');
		$connection = new WidgetConnection([
			'connection_id' => 669,
			'widget_name' => WidgetList::COMPANYLIST,
		]);
		$widget = new WidgetCompanyList();

		$tree = $widget->buildTree($composer, $connection);

		$this->assertSame('companyList', $tree['component']);
		$this->assertSame(
			WidgetCompanyList::buildStrings()['company.list.title'],
			$tree['strings']['company.list.title'] ?? null
		);
		$this->assertSame(
			WidgetCompanyList::buildStrings()['datatable.column_visibility'],
			$tree['strings']['datatable.column_visibility'] ?? null
		);
	}

	public function testHtmlTreeRendererRendersCompanyDescriptionWithResolvedStrings(): void
	{
		$composer = new TreeRenderingTestComposer('Tracker', 'public_default');
		$renderer = new HtmlTreeRenderer(theme: $composer->getTheme());
		$strings = WidgetCompanyDescription::buildStrings();

		$output = $renderer->render([
			'component' => 'companyDescription',
			'props' => [
				'companyData' => [
					'id' => 7,
					'name' => 'Example Company',
					'shortname' => 'EXM',
				],
				'modificationsList' => [],
			],
			'slots' => [],
			'strings' => $strings,
		]);

		$this->assertStringContainsString($strings['company.description.widget_name'], $output);
		$this->assertStringContainsString($strings['company.description.back_to_list'], $output);
		$this->assertStringContainsString($strings['company.field.shortname.label'], $output);
		$this->assertStringContainsString($strings['common.no_data'], $output);
	}

	public function testContactPersonListBuildsResolvedStrings(): void
	{
		$composer = new TreeRenderingTestComposer('Tracker', 'public_default');
		$connection = new WidgetConnection([
			'connection_id' => 670,
			'widget_name' => WidgetList::CONTACTPERSONLIST,
		]);
		$widget = new WidgetContactPersonList();

		$tree = $widget->buildTree($composer, $connection);

		$this->assertSame('contactPersonList', $tree['component']);
		$this->assertSame(
			WidgetContactPersonList::buildStrings()['contact.list.title'],
			$tree['strings']['contact.list.title'] ?? null
		);
		$this->assertSame(
			WidgetContactPersonList::buildStrings()['record_action.datasheet'],
			$tree['strings']['record_action.datasheet'] ?? null
		);
	}

	public function testHtmlTreeRendererRendersContactPersonDescriptionWithResolvedStrings(): void
	{
		$composer = new TreeRenderingTestComposer('Tracker', 'public_default');
		$renderer = new HtmlTreeRenderer(theme: $composer->getTheme());
		$strings = WidgetContactPersonDescription::buildStrings();

		$output = $renderer->render([
			'component' => 'contactPersonDescription',
			'props' => [
				'contactPersonData' => [
					'id' => 8,
					'name' => 'Jane Doe',
					'company' => 'Example Company',
					'connected_company_id' => 7,
				],
				'modificationsList' => [],
			],
			'slots' => [],
			'strings' => $strings,
		]);

		$this->assertStringContainsString($strings['contact.description.widget_name'], $output);
		$this->assertStringContainsString($strings['contact.description.back_to_list'], $output);
		$this->assertStringContainsString($strings['contact.col.company'], $output);
		$this->assertStringContainsString($strings['common.no_data'], $output);
	}

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

final class TreeRenderingBlogListWidget extends WidgetBlogList
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

final class TreeRenderingPublicUserListWidget extends WidgetPublicUserList
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
