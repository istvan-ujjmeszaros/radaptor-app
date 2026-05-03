<?php

declare(strict_types=1);

final class SkeletonBootstrapSeedTest extends TransactionedTestCase
{
	public function testBootstrapSeedEnsuresAdminHomepageLoginAndCoreAdminPages(): void
	{
		$username = 'skeleton_seed_admin';
		$password = 'skeleton_seed_password';

		TestHelperEnvironment::setEnvironmentVariable('APP_BOOTSTRAP_ADMIN_USERNAME', $username);
		TestHelperEnvironment::setEnvironmentVariable('APP_BOOTSTRAP_ADMIN_PASSWORD', $password);
		TestHelperEnvironment::setEnvironmentVariable('APP_BOOTSTRAP_ADMIN_LOCALE', 'en_US');
		TestHelperEnvironment::setEnvironmentVariable('APP_BOOTSTRAP_ADMIN_TIMEZONE', 'UTC');
		TestHelperEnvironment::setEnvironmentVariable('APP_DOMAIN_CONTEXT', 'tracker.virtuosoft.hu');

		try {
			$seed = new SeedSkeletonBootstrap();
			$seed->run(new SeedContext('app', 'mandatory', DEPLOY_ROOT . 'app', false));

			$user = User::getUserByName($username);
			$this->assertIsArray($user);
			$this->assertSame(1, (int) $user['is_active']);

			$system_developer_role_id = (int) DbHelper::selectOneColumn('roles_tree', [
				'role' => 'system_developer',
			], '', 'node_id');
			$system_admin_role_id = (int) DbHelper::selectOneColumn('roles_tree', [
				'role' => 'system_administrator',
			], '', 'node_id');
			$this->assertGreaterThan(0, $system_developer_role_id);
			$this->assertGreaterThan(0, $system_admin_role_id);
			$this->assertTrue(Roles::checkUserIsAssigned($system_developer_role_id, (int) $user['user_id']));
			$this->assertTrue(Roles::checkUserIsAssigned($system_admin_role_id, (int) $user['user_id']));
			$everyone_id = (int) DbHelper::selectOneColumn('usergroups_tree', ['title' => 'Everyone'], '', 'node_id');
			$logged_in_users_id = (int) DbHelper::selectOneColumn('usergroups_tree', ['title' => 'Logged in users'], '', 'node_id');
			$administrators_id = (int) DbHelper::selectOneColumn('usergroups_tree', ['title' => 'Administrators'], '', 'node_id');
			$this->assertGreaterThan(0, $everyone_id);
			$this->assertGreaterThan(0, $logged_in_users_id);
			$this->assertGreaterThan(0, $administrators_id);
			$this->assertGreaterThan(0, (int) DbHelper::selectOneColumn('roles_tree', ['role' => 'acl_viewer'], '', 'node_id'));

			$homepage = ResourceTreeHandler::getResourceTreeEntryData('/', 'index.html', Config::APP_DOMAIN_CONTEXT->value());
			$this->assertIsArray($homepage);
			$root_id = ResourceTreeHandler::getDomainRoot(Config::APP_DOMAIN_CONTEXT->value());
			$this->assertIsInt($root_id);
			$this->assertGreaterThan(0, $root_id);
			$root_logged_in_acl = DbHelper::selectOne('resource_acl', [
				'resource_id' => $root_id,
				'subject_type' => 'usergroup',
				'subject_id' => $logged_in_users_id,
			], '', 'allow_view,allow_list');
			$this->assertSame(1, (int) ($root_logged_in_acl['allow_view'] ?? 0));
			$this->assertSame(1, (int) ($root_logged_in_acl['allow_list'] ?? 0));
			$root_everyone_acl = DbHelper::selectOne('resource_acl', [
				'resource_id' => $root_id,
				'subject_type' => 'usergroup',
				'subject_id' => $everyone_id,
			], '', 'acl_id');
			$this->assertFalse(is_array($root_everyone_acl));
			$homepage_connection_id = Widget::getWidgetConnectionId((int) $homepage['node_id'], 'content', WidgetList::PLAINHTML);
			$this->assertIsInt($homepage_connection_id);
			$homepage_settings = PlainHtml::getSettings($homepage_connection_id);
			$this->assertStringContainsString('Radaptor App', (string) ($homepage_settings['content'] ?? ''));

			$login_page = ResourceTreeHandler::getResourceTreeEntryData('/', 'login.html', Config::APP_DOMAIN_CONTEXT->value());
			$this->assertIsArray($login_page);
			$this->assertSame(0, (int) ($login_page['is_inheriting_acl'] ?? 1));
			$login_acl = DbHelper::selectOne('resource_acl', [
				'resource_id' => (int) $login_page['node_id'],
				'subject_type' => 'usergroup',
				'subject_id' => $everyone_id,
			], '', 'allow_view,allow_list');
			$this->assertIsArray($login_acl);
			$this->assertSame(1, (int) ($login_acl['allow_view'] ?? 0));
			$this->assertSame(1, (int) ($login_acl['allow_list'] ?? 0));
			$login_admin_acl = DbHelper::selectOne('resource_acl', [
				'resource_id' => (int) $login_page['node_id'],
				'subject_type' => 'usergroup',
				'subject_id' => $administrators_id,
			], '', 'allow_edit');
			$this->assertIsArray($login_admin_acl);
			$this->assertSame(1, (int) ($login_admin_acl['allow_edit'] ?? 0));
			$form_connection_id = Widget::getWidgetConnectionId((int) $login_page['node_id'], 'content', WidgetList::FORM);
			$this->assertIsInt($form_connection_id);
			$form_attributes = AttributeHandler::getAttributes(
				new AttributeResourceIdentifier(ResourceNames::WIDGET_CONNECTION, (string) $form_connection_id)
			);
			$this->assertSame(FormList::USERLOGIN, $form_attributes['form_id'] ?? null);
			$this->assertSame('auto', $form_attributes['margin-left'] ?? null);
			$this->assertSame('auto', $form_attributes['margin-right'] ?? null);
			$this->assertSame('min(100%, 28rem)', $form_attributes['width'] ?? null);
			$this->assertStringNotContainsString('/login/', (string) ($homepage_settings['content'] ?? ''));

			foreach ([
				'/admin/users/' => WidgetList::USERLIST,
				'/admin/usergroups/' => WidgetList::USERGROUPLIST,
				'/admin/roles/' => WidgetList::ROLELIST,
				'/admin/resources/' => WidgetList::RESOURCETREE,
				'/admin/components/adminmenu/' => WidgetList::ADMINMENU,
			] as $path => $widget_name) {
				$page = ResourceTreeHandler::getResourceTreeEntryData($path, 'index.html', Config::APP_DOMAIN_CONTEXT->value());
				$this->assertIsArray($page, "Expected autogenerated page for {$path}");
				$this->assertIsInt(Widget::getWidgetConnectionId((int) $page['node_id'], 'content', $widget_name));
			}

			$admin_folder = ResourceTreeHandler::getResourceTreeEntryData('/', 'admin', Config::APP_DOMAIN_CONTEXT->value());
			$this->assertIsArray($admin_folder);
			$this->assertSame('folder', $admin_folder['node_type'] ?? null);
			$this->assertSame(0, (int) ($admin_folder['is_inheriting_acl'] ?? 1));
			$admin_acl = DbHelper::selectOne('resource_acl', [
				'resource_id' => (int) $admin_folder['node_id'],
				'subject_type' => 'usergroup',
				'subject_id' => $administrators_id,
			], '', 'allow_view,allow_list');
			$this->assertSame(1, (int) ($admin_acl['allow_view'] ?? 0));
			$this->assertSame(1, (int) ($admin_acl['allow_list'] ?? 0));

			$admin_index_page = ResourceTreeHandler::getResourceTreeEntryData('/admin/', 'index.html', Config::APP_DOMAIN_CONTEXT->value());
			$this->assertIsArray($admin_index_page);
			$this->assertSame(
				['PlainHtml'],
				array_map(
					static fn (WidgetConnection $connection): string => $connection->getWidgetName(),
					WidgetConnection::getWidgetsForSlot((int) $admin_index_page['node_id'], ResourceTypeWebpage::DEFAULT_SLOT_NAME)
				)
			);
			$admin_index_connection_id = Widget::getWidgetConnectionId((int) $admin_index_page['node_id'], 'content', WidgetList::PLAINHTML);
			$this->assertIsInt($admin_index_connection_id);
			$admin_index_settings = PlainHtml::getSettings($admin_index_connection_id);
			$this->assertStringContainsString('Welcome to Radaptor App', (string) ($admin_index_settings['content'] ?? ''));
		} finally {
			TestHelperEnvironment::revertEnvironmentVariable('APP_DOMAIN_CONTEXT');
			TestHelperEnvironment::revertEnvironmentVariable('APP_BOOTSTRAP_ADMIN_TIMEZONE');
			TestHelperEnvironment::revertEnvironmentVariable('APP_BOOTSTRAP_ADMIN_LOCALE');
			TestHelperEnvironment::revertEnvironmentVariable('APP_BOOTSTRAP_ADMIN_PASSWORD');
			TestHelperEnvironment::revertEnvironmentVariable('APP_BOOTSTRAP_ADMIN_USERNAME');
		}
	}
}
