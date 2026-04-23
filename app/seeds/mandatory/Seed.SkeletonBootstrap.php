<?php

class SeedSkeletonBootstrap extends AbstractSeed
{
	private const string ROLE_SYSTEM_DEVELOPER = 'system_developer';
	private const string ROLE_SYSTEM_ADMINISTRATOR = 'system_administrator';

	private CmsSeedHelper $_cms;

	public function getVersion(): string
	{
		return '1.10.1';
	}

	public function getDescription(): string
	{
		return 'Ensure bootstrap admin, homepage, login page, and core admin pages.';
	}

	public function run(SeedContext $context): void
	{
		Cache::flush();
		$this->_cms = new CmsSeedHelper($context);

		$this->ensureSecurityBaseline();
		$this->ensureBootstrapAdmin();
		$this->ensureHomepage();
		$this->ensureAdminIndex();
		$this->ensureLoginPages();
		$this->ensureAdminPages();
	}

	private function ensureBootstrapAdmin(): void
	{
		$username = $this->getStringSetting('APP_BOOTSTRAP_ADMIN_USERNAME', 'admin');
		$password = $this->getStringSetting('APP_BOOTSTRAP_ADMIN_PASSWORD', 'admin123456');
		$locale = $this->getStringSetting('APP_BOOTSTRAP_ADMIN_LOCALE', 'en_US');
		$timezone = $this->getStringSetting('APP_BOOTSTRAP_ADMIN_TIMEZONE', 'UTC');
		$existing = User::getUserByName($username);

		if ($existing === null) {
			$user = EntityUser::saveFromArray([
				'username' => $username,
				'password' => UserBase::encodePassword($password),
				'is_active' => 1,
				'locale' => $locale,
				'timezone' => $timezone,
			]);
			$user_id = (int) $user->user_id;
		} else {
			$user = EntityUser::saveFromArray([
				'user_id' => (int) $existing['user_id'],
				'username' => $username,
				'is_active' => 1,
				'locale' => $locale,
				'timezone' => $timezone,
			]);
			$user_id = (int) $user->user_id;
		}

		$this->ensureUserHasRole($user_id, self::ROLE_SYSTEM_ADMINISTRATOR);
		$this->ensureUserHasRole($user_id, self::ROLE_SYSTEM_DEVELOPER);
		$this->ensureUserInUsergroup($user_id, 'Administrators');
		$this->ensureUserInUsergroup($user_id, 'Developers');
	}

	private function ensureSecurityBaseline(): void
	{
		$this->ensureRoleBranch($this->getRoleTreeDefinitions());
		$this->ensureUsergroupBranch($this->getUsergroupTreeDefinitions());
		$this->ensureUsergroupHasRole('Administrators', self::ROLE_SYSTEM_ADMINISTRATOR);
		$this->ensureUsergroupHasRole('Developers', self::ROLE_SYSTEM_DEVELOPER);
	}

	private function ensureHomepage(): void
	{
		$page_id = $this->ensureWebpage('/', 'index.html', 'public_empty');
		$this->ensureRootAclBaseline();
		$connection_id = $this->ensureWidget($page_id, WidgetList::PLAINHTML);

		PlainHtml::saveSettings([
			'content' => '<h1>Radaptor App</h1><p>Your application skeleton is installed and ready.</p><p>Open <a href="/admin/index.html">the admin area</a> to configure users, roles, resources, and content.</p>',
		], $connection_id);
	}

	private function ensureAdminIndex(): void
	{
		$this->_cms->upsertFolder([
			'path' => '/admin/',
		]);
		$this->ensureAdminAclBaseline();
		$this->_cms->upsertWebpage([
			'path' => '/admin/',
			'layout' => 'admin_default',
			'attributes' => [
				'title' => 'Radaptor admin',
				'description' => 'Bootstrap administration dashboard.',
			],
			'acl' => [
				'inherit' => true,
				'usergroups' => [],
			],
			'slots' => [
				ResourceTypeWebpage::DEFAULT_SLOT_NAME => [
					[
						'widget' => WidgetList::PLAINHTML,
						'settings' => [
							'content' => $this->getAdminWelcomeHtml(),
						],
					],
				],
			],
		]);
	}

	private function ensureLoginPages(): void
	{
		$page_id = $this->ensureWebpage('/', 'login.html', 'admin_empty');
		$this->ensureLoginAclBaseline($page_id);
		$connection_id = $this->ensureWidget($page_id, WidgetList::FORM, false);

		AttributeHandler::addAttribute(
			new AttributeResourceIdentifier(ResourceNames::WIDGET_CONNECTION, (string) $connection_id),
			['form_id' => FormList::USERLOGIN]
		);
	}

	private function ensureAdminPages(): void
	{
		foreach ([
			FormList::PLAINHTML,
			FormList::THEMESELECTOR,
			FormList::WEBPAGEPAGE,
			FormList::WIDGETCONNECTIONPARAMS,
			FormList::WIDGETCONNECTIONSETTINGS,
			FormList::ROLE,
			FormList::USER,
			FormList::USERGROUP,
		] as $form_id) {
			ResourceTypeWebpage::getWebpageIdByFormType($form_id);
		}

		foreach ([
			WidgetList::USERLIST,
			WidgetList::USERGROUPLIST,
			WidgetList::ROLELIST,
			WidgetList::RESOURCETREE,
			WidgetList::ADMINMENU,
			WidgetList::IMPORTEXPORT,
			WidgetList::I18NWORKBENCH,
			WidgetList::WIDGETPREVIEW,
		] as $widget_name) {
			ResourceTypeWebpage::findWebpageIdWithWidget($widget_name);
		}
	}

	private function ensureWebpage(string $path, string $resource_name, string $layout): int
	{
		$page = ResourceTreeHandler::getResourceTreeEntryData($path, $resource_name);

		if ($page !== null) {
			return (int) $page['node_id'];
		}

		$page_id = ResourceTreeHandler::createResourceTreeEntryFromPath($path, $resource_name, 'webpage', $layout);

		if (is_int($page_id) && $page_id > 0) {
			return $page_id;
		}

		$existing_page_id = $this->findExistingWebpageByUniquePath($path, $resource_name);

		if (is_int($existing_page_id) && $existing_page_id > 0) {
			ResourceTreeHandler::updateResourceTreeEntry(['layout' => $layout], $existing_page_id);

			return $existing_page_id;
		}

		throw new RuntimeException("Unable to create webpage {$path}{$resource_name}");
	}

	private function findExistingWebpageByUniquePath(string $path, string $resource_name): ?int
	{
		$row = DbHelper::selectOne('resource_tree', [
			'node_type' => 'webpage',
			'path' => $path,
			'resource_name' => $resource_name,
		], '', 'node_id');

		if (!is_array($row) || !is_numeric($row['node_id'] ?? null)) {
			return null;
		}

		return (int) $row['node_id'];
	}

	private function ensureWidget(int $page_id, string $widget_name, bool $multiple = true): int
	{
		$existing_connection_id = Widget::getWidgetConnectionId($page_id, ResourceTypeWebpage::DEFAULT_SLOT_NAME, $widget_name);

		if (is_int($existing_connection_id) && $existing_connection_id > 0) {
			return $existing_connection_id;
		}

		$connection_id = Widget::assignWidgetToWebpage(
			$page_id,
			ResourceTypeWebpage::DEFAULT_SLOT_NAME,
			$widget_name,
			null,
			$multiple
		);

		if (!is_int($connection_id) || $connection_id <= 0) {
			throw new RuntimeException("Unable to assign widget {$widget_name} to webpage {$page_id}");
		}

		return $connection_id;
	}

	private function getStringSetting(string $name, string $default): string
	{
		$env = getenv($name);

		if (is_string($env) && trim($env) !== '') {
			return trim($env);
		}

		if (defined(ApplicationConfig::class . '::' . $name)) {
			$value = constant(ApplicationConfig::class . '::' . $name);

			if (is_string($value) && trim($value) !== '') {
				return trim($value);
			}
		}

		return $default;
	}

	private function getAdminWelcomeHtml(): string
	{
		return <<<HTML
			<div class="card card-hover email-admin-welcome">
				<div class="card-body">
					<h2 class="h3 mb-3">Welcome to Radaptor App</h2>
					<p class="mb-2">This admin shell was bootstrapped from the default mandatory seed.</p>
					<p class="mb-0">Use the side menu to manage users, roles, and resources.</p>
				</div>
			</div>
			HTML;
	}

	/**
	 * @return list<array{
	 *     title: string,
	 *     description: string,
	 *     role: string,
	 *     _?: list<array<string, mixed>>
	 * }>
	 */
	private function getRoleTreeDefinitions(): array
	{
		return [
			['title' => 'Developer', 'description' => 'Developer', 'role' => self::ROLE_SYSTEM_DEVELOPER, '_' => [
				['title' => 'Usergroup administrator', 'description' => 'Usergroup admin', 'role' => 'usergroups_admin', '_' => [
					['title' => 'Usergroup role administrator', 'description' => 'Usergroup role admin', 'role' => 'usergroups_role_admin'],
				]],
				['title' => 'Role administrator', 'description' => 'Role admin', 'role' => 'roles_admin', '_' => [
					['title' => 'Role viewer', 'description' => 'Role viewer', 'role' => 'roles_viewer'],
				]],
				['title' => 'Domain administrator', 'description' => 'Domain admin', 'role' => 'domains_admin'],
				['title' => 'Resource ACL administrator', 'description' => 'Resource ACL admin', 'role' => 'acl_admin'],
			]],
			['title' => 'System administrator', 'description' => 'System administrator', 'role' => self::ROLE_SYSTEM_ADMINISTRATOR, '_' => [
				['title' => 'Translator', 'description' => 'Manage i18n translations in the workbench and import/export flows', 'role' => 'i18n_translator'],
				['title' => 'Richtext administrator', 'description' => 'Richtext admin', 'role' => 'richtext_administrator'],
				['title' => 'Blog administrator', 'description' => 'Blog admin', 'role' => 'blog_admin'],
				['title' => 'Email administrator', 'description' => 'Email administration and sending', 'role' => 'emails_admin'],
				['title' => 'Resource ACL viewer', 'description' => 'Resource ACL viewer', 'role' => 'acl_viewer'],
				['title' => 'User administration', 'description' => 'User admin', 'role' => 'users_admin', '_' => [
					['title' => 'User role administrator', 'description' => 'User role admin', 'role' => 'users_role_admin'],
					['title' => 'User usergroup administrator', 'description' => 'User usergroup admin', 'role' => 'users_usergroup_admin'],
				]],
				['title' => 'File administrator', 'description' => 'File uploader', 'role' => 'files_admin'],
				['title' => 'Content editor', 'description' => 'Content editor', 'role' => 'content_admin'],
				['title' => 'Timetracker administrator', 'description' => 'Timetracker admin', 'role' => 'timetracker_administrator', '_' => [
					['title' => 'Timetracker viewer', 'description' => 'Timetracker viewer', 'role' => 'timetracker_viewer'],
				]],
			]],
		];
	}

	/**
	 * @return list<array{
	 *     title: string,
	 *     description: string,
	 *     is_system_group: int,
	 *     _?: list<array<string, mixed>>
	 * }>
	 */
	private function getUsergroupTreeDefinitions(): array
	{
		return [
			['is_system_group' => 1, 'title' => 'Everyone', 'description' => 'Everyone', '_' => [
				['is_system_group' => 1, 'title' => 'Logged in users', 'description' => 'Logged in users'],
				['is_system_group' => 0, 'title' => 'Administrators', 'description' => 'Administrators'],
				['is_system_group' => 0, 'title' => 'Developers', 'description' => 'Developers'],
			]],
		];
	}

	/**
	 * @param list<array<string, mixed>> $definitions
	 */
	private function ensureRoleBranch(array $definitions, int $parent_id = 0): void
	{
		foreach ($definitions as $definition) {
			$role_id = $this->ensureRole(
				(string) $definition['role'],
				(string) $definition['title'],
				(string) $definition['description'],
				$parent_id
			);

			if (isset($definition['_']) && is_array($definition['_'])) {
				$this->ensureRoleBranch($definition['_'], $role_id);
			}
		}
	}

	private function ensureRole(string $role, string $title, string $description, int $parent_id): int
	{
		$existing = DbHelper::selectOne('roles_tree', ['role' => $role], '', 'node_id,parent_id,title,description');

		if (!is_array($existing)) {
			$role_id = Roles::addRole([
				'role' => $role,
				'title' => $title,
				'description' => $description,
			], $parent_id);

			if (!is_int($role_id) || $role_id <= 0) {
				throw new RuntimeException("Unable to create role {$role}");
			}

			return $role_id;
		}

		$role_id = (int) $existing['node_id'];

		if ((int) $existing['parent_id'] !== $parent_id && !Roles::moveToPosition($role_id, $parent_id, 0)) {
			throw new RuntimeException("Unable to move role {$role}");
		}

		$save_data = ['node_id' => $role_id];

		if ((string) ($existing['title'] ?? '') !== $title) {
			$save_data['title'] = $title;
		}

		if ((string) ($existing['description'] ?? '') !== $description) {
			$save_data['description'] = $description;
		}

		if (count($save_data) > 1) {
			if (Roles::updateRole($save_data, $role_id) < 0) {
				throw new RuntimeException("Unable to update role {$role}");
			}
		}

		return $role_id;
	}

	/**
	 * @param list<array<string, mixed>> $definitions
	 */
	private function ensureUsergroupBranch(array $definitions, int $parent_id = 0): void
	{
		foreach ($definitions as $definition) {
			$usergroup_id = $this->ensureUsergroup(
				(string) $definition['title'],
				(string) $definition['description'],
				(int) $definition['is_system_group'],
				$parent_id
			);

			if (isset($definition['_']) && is_array($definition['_'])) {
				$this->ensureUsergroupBranch($definition['_'], $usergroup_id);
			}
		}
	}

	private function ensureUsergroup(string $title, string $description, int $is_system_group, int $parent_id): int
	{
		$existing = DbHelper::selectOne('usergroups_tree', ['title' => $title], '', 'node_id,parent_id,description,is_system_group');

		if (!is_array($existing)) {
			$usergroup_id = Usergroups::addUsergroup([
				'title' => $title,
				'description' => $description,
				'is_system_group' => $is_system_group,
			], $parent_id);

			if (!is_int($usergroup_id) || $usergroup_id <= 0) {
				throw new RuntimeException("Unable to create usergroup {$title}");
			}

			return $usergroup_id;
		}

		$usergroup_id = (int) $existing['node_id'];

		if ((int) $existing['parent_id'] !== $parent_id && !Usergroups::moveToPosition($usergroup_id, $parent_id, 0)) {
			throw new RuntimeException("Unable to move usergroup {$title}");
		}

		$save_data = ['node_id' => $usergroup_id];

		if ((string) ($existing['description'] ?? '') !== $description) {
			$save_data['description'] = $description;
		}

		if ((int) ($existing['is_system_group'] ?? 0) !== $is_system_group) {
			$save_data['is_system_group'] = $is_system_group;
		}

		if (count($save_data) > 1) {
			if (Usergroups::updateUsergroup($save_data, $usergroup_id) < 0) {
				throw new RuntimeException("Unable to update usergroup {$title}");
			}
		}

		return $usergroup_id;
	}

	private function ensureUsergroupHasRole(string $title, string $role): void
	{
		$usergroup = DbHelper::selectOne('usergroups_tree', ['title' => $title], '', 'node_id');
		$role_row = DbHelper::selectOne('roles_tree', ['role' => $role], '', 'node_id');

		if (!is_array($usergroup) || !is_array($role_row)) {
			throw new RuntimeException("Unable to map role {$role} to usergroup {$title}");
		}

		$usergroup_id = (int) $usergroup['node_id'];
		$role_id = (int) $role_row['node_id'];

		if (!Roles::checkUsergroupIsAssigned($role_id, $usergroup_id) && !Roles::assignToUsergroup($role_id, $usergroup_id)) {
			throw new RuntimeException("Unable to assign role {$role} to usergroup {$title}");
		}
	}

	private function ensureUserHasRole(int $user_id, string $role): void
	{
		$role_row = DbHelper::selectOne('roles_tree', ['role' => $role], '', 'node_id');

		if (!is_array($role_row)) {
			throw new RuntimeException("Role not found: {$role}");
		}

		$role_id = (int) $role_row['node_id'];

		if (!Roles::checkUserIsAssigned($role_id, $user_id) && !Roles::assignToUser($role_id, $user_id)) {
			throw new RuntimeException("Unable to assign role {$role} to user {$user_id}");
		}
	}

	private function ensureUserInUsergroup(int $user_id, string $title): void
	{
		$usergroup = DbHelper::selectOne('usergroups_tree', ['title' => $title], '', 'node_id');

		if (!is_array($usergroup)) {
			throw new RuntimeException("Usergroup not found: {$title}");
		}

		$usergroup_id = (int) $usergroup['node_id'];

		if (!Usergroups::checkUserIsAssigned($usergroup_id, $user_id) && !Usergroups::assignToUser($usergroup_id, $user_id)) {
			throw new RuntimeException("Unable to assign user {$user_id} to usergroup {$title}");
		}
	}

	private function ensureRootAclBaseline(): void
	{
		$root_id = ResourceTreeHandler::getDomainRoot(Config::APP_DOMAIN_CONTEXT->value());

		if (!is_int($root_id) || $root_id <= 0) {
			throw new RuntimeException('Domain root not found');
		}

		$logged_in_users_id = $this->getUsergroupIdByTitle('Logged in users');
		$administrators_id = $this->getUsergroupIdByTitle('Administrators');

		$this->ensureUsergroupAcl($logged_in_users_id, $root_id, [
			'allow_view' => 1,
			'allow_list' => 1,
		], 'Logged in users');

		$this->ensureUsergroupAcl($administrators_id, $root_id, [
			'allow_edit' => 1,
		], 'Administrators');
	}

	private function ensureAdminAclBaseline(): void
	{
		$admin_folder = ResourceTreeHandler::getResourceTreeEntryData('/', 'admin', Config::APP_DOMAIN_CONTEXT->value());

		if (!is_array($admin_folder) || ($admin_folder['node_type'] ?? null) !== 'folder') {
			throw new RuntimeException('Admin folder not found');
		}

		$admin_folder_id = (int) $admin_folder['node_id'];
		ResourceAcl::setInheritance($admin_folder_id, false);

		$administrators_id = $this->getUsergroupIdByTitle('Administrators');

		$this->ensureUsergroupAcl($administrators_id, $admin_folder_id, [
			'allow_view' => 1,
			'allow_list' => 1,
		], 'Administrators');
	}

	private function ensureLoginAclBaseline(int $page_id): void
	{
		ResourceAcl::setInheritance($page_id, false);

		$everyone_id = $this->getUsergroupIdByTitle('Everyone');
		$administrators_id = $this->getUsergroupIdByTitle('Administrators');

		$this->ensureUsergroupAcl($everyone_id, $page_id, [
			'allow_view' => 1,
			'allow_list' => 1,
		], 'Everyone');

		$this->ensureUsergroupAcl($administrators_id, $page_id, [
			'allow_edit' => 1,
		], 'Administrators');
	}

	private function getUsergroupIdByTitle(string $title): int
	{
		$usergroup_id = (int) DbHelper::selectOneColumn('usergroups_tree', ['title' => $title], '', 'node_id');

		if ($usergroup_id <= 0) {
			throw new RuntimeException("Usergroup not found: {$title}");
		}

		return $usergroup_id;
	}

	private function ensureUsergroupAcl(int $usergroup_id, int $resource_id, array $permissions, string $title): void
	{
		$acl = DbHelper::selectOne('resource_acl', [
			'resource_id' => $resource_id,
			'subject_type' => 'usergroup',
			'subject_id' => $usergroup_id,
		], '', 'acl_id');

		if (!is_array($acl)) {
			if (!ResourceAcl::assignToUsergroup($usergroup_id, $resource_id)) {
				throw new RuntimeException("Unable to assign {$title} ACL to resource {$resource_id}");
			}

			$acl = DbHelper::selectOne('resource_acl', [
				'resource_id' => $resource_id,
				'subject_type' => 'usergroup',
				'subject_id' => $usergroup_id,
			], '', 'acl_id');
		}

		if (!is_array($acl)) {
			throw new RuntimeException("Unable to load {$title} ACL for resource {$resource_id}");
		}

		$save_data = [];

		foreach ($permissions as $column => $expected_value) {
			$current_value = (int) DbHelper::selectOneColumn('resource_acl', ['acl_id' => (int) $acl['acl_id']], '', $column);

			if ($current_value !== (int) $expected_value) {
				$save_data[$column] = (int) $expected_value;
			}
		}

		if ($save_data !== []) {
			ResourceAcl::updateAcl((int) $acl['acl_id'], $save_data);
		}
	}
}
