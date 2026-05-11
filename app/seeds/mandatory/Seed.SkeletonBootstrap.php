<?php

class SeedSkeletonBootstrap extends AbstractSeed
{
	private const string ROLE_SYSTEM_DEVELOPER = 'system_developer';
	private const string ROLE_SYSTEM_ADMINISTRATOR = 'system_administrator';
	private const string USERGROUP_EVERYONE = 'Everyone';
	private const string USERGROUP_LOGGED_IN = 'Logged in users';
	private const string USERGROUP_ADMINISTRATORS = 'Administrators';
	private const string USERGROUP_DEVELOPERS = 'Developers';
	private const string BOOTSTRAP_OWNER_ATTRIBUTE = 'radaptor_bootstrap_owner';
	private const string BOOTSTRAP_OWNER = 'skeleton';
	private const array STABLE_SYSTEM_USERGROUP_IDS = [
		self::USERGROUP_EVERYONE => Usergroups::SYSTEMUSERGROUP_EVERYBODY,
		self::USERGROUP_LOGGED_IN => Usergroups::SYSTEMUSERGROUP_LOGGEDIN,
	];
	private const array BOOTSTRAP_USERGROUP_ROLES = [
		self::USERGROUP_ADMINISTRATORS => self::ROLE_SYSTEM_ADMINISTRATOR,
		self::USERGROUP_DEVELOPERS => self::ROLE_SYSTEM_DEVELOPER,
	];
	private string $_siteContext = 'app';

	public function getVersion(): string
	{
		return '1.10.6';
	}

	public function getRunPolicy(): string
	{
		return self::RUN_POLICY_BOOTSTRAP_ONCE;
	}

	public function getDescription(): string
	{
		return 'Ensure bootstrap admin, login page, and core admin pages.';
	}

	public function run(SeedContext $context): void
	{
		$this->_siteContext = ResourceTreeHandler::getActiveDomainContext();

		ResourceTreeHandler::withProtectedResourceMutationBypass(function (): void {
			Cache::flush();
			LocaleAdminService::ensureDefaultLocaleRegistered();

			$this->ensureSecurityBaseline();
			$this->ensureBootstrapAdmin();
			$can_manage_admin_pages = $this->ensureAdminIndex();
			$this->ensureLoginPages();

			if ($can_manage_admin_pages) {
				$this->ensureAdminPages();
			}
		});
	}

	private function ensureBootstrapAdmin(): void
	{
		$username = $this->getStringSetting('APP_BOOTSTRAP_ADMIN_USERNAME', 'admin');
		$password = $this->getStringSetting('APP_BOOTSTRAP_ADMIN_PASSWORD', 'admin123456');
		$locale = LocaleService::canonicalize($this->getStringSetting('APP_BOOTSTRAP_ADMIN_LOCALE', 'en-US'));
		$timezone = $this->getStringSetting('APP_BOOTSTRAP_ADMIN_TIMEZONE', 'UTC');
		$existing = User::getUserByName($username);

		LocaleAdminService::ensureLocale($locale, true);

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
		$this->ensureUserInUsergroup($user_id, self::USERGROUP_ADMINISTRATORS);
		$this->ensureUserInUsergroup($user_id, self::USERGROUP_DEVELOPERS);
	}

	private function ensureSecurityBaseline(): void
	{
		$this->ensureRoleBranch($this->getRoleTreeDefinitions());
		$this->ensureUsergroupBranch($this->getUsergroupTreeDefinitions());
		$this->ensureUsergroupHasRole(self::USERGROUP_ADMINISTRATORS, self::ROLE_SYSTEM_ADMINISTRATOR);
		$this->ensureUsergroupHasRole(self::USERGROUP_DEVELOPERS, self::ROLE_SYSTEM_DEVELOPER);
	}

	private function ensureAdminIndex(): bool
	{
		$admin_folder = $this->ensureBootstrapFolder('/admin/');

		if (!$this->canManageBootstrapResource($admin_folder['resource_id'], $admin_folder['created'])) {
			return false;
		}

		$this->ensureAdminAclBaseline($admin_folder['resource_id']);
		$admin_page = $this->ensureBootstrapWebpage('/admin/', 'index.html', 'admin_default');

		if (!$this->canManageBootstrapResource($admin_page['resource_id'], $admin_page['created'])) {
			return false;
		}

		ResourceTreeHandler::updateResourceTreeEntry([
			'layout' => 'admin_default',
			'title' => 'Radaptor admin',
			'description' => 'Bootstrap administration dashboard.',
		], $admin_page['resource_id']);
		ResourceAcl::setInheritance($admin_page['resource_id'], true);
		$connection_id = $this->ensureWidget($admin_page['resource_id'], WidgetList::PLAINHTML, false);

		PlainHtml::saveSettings([
			'content' => $this->getAdminWelcomeHtml(),
		], $connection_id);

		return true;
	}

	private function ensureLoginPages(): void
	{
		$login_layout = 'admin_login';
		$page = $this->ensureBootstrapWebpage('/', 'login.html', $login_layout);

		if (!$this->canManageBootstrapResource($page['resource_id'], $page['created'])) {
			return;
		}

		ResourceTreeHandler::updateResourceTreeEntry(['layout' => $login_layout], $page['resource_id']);
		$this->ensureLoginAclBaseline($page['resource_id']);
		$connection_id = $this->ensureWidget($page['resource_id'], WidgetList::FORM, false);

		AttributeHandler::addAttribute(
			new AttributeResourceIdentifier(ResourceNames::WIDGET_CONNECTION, (string) $connection_id),
			[
				'form_id' => FormList::USERLOGIN,
				'margin-left' => 'auto',
				'margin-right' => 'auto',
				'width' => 'min(100%, 28rem)',
			]
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
			$this->ensureDefaultFormPage($form_id);
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
			$this->ensureDefaultWidgetPage($widget_name);
		}

		foreach ([
			'PHPINFOFRAME',
			'CLIRUNNER',
			'RUNTIMEDIAGNOSTICS',
			'TEMPLATEENGINEDEMOPHP',
			'TEMPLATEENGINEDEMOBLADE',
			'TEMPLATEENGINEDEMOTWIG',
		] as $widget_constant) {
			$constant_name = WidgetList::class . '::' . $widget_constant;

			if (defined($constant_name)) {
				$this->ensureDefaultWidgetPage((string) constant($constant_name));
			}
		}
	}

	private function ensureDefaultFormPage(string $form_id): int
	{
		$path_data = $this->getDefaultPathDataForForm($form_id);
		$existing_page = ResourceTreeHandler::getResourceTreeEntryData($path_data['path'], $path_data['resource_name'], $this->_siteContext);

		if (is_array($existing_page) && !$this->isSkeletonOwnedResource((int) $existing_page['node_id'])) {
			return (int) $existing_page['node_id'];
		}

		$page_id = ResourceTypeWebpage::ensureDefaultWebpageWithFormType($form_id);

		if ($page_id === false) {
			throw new RuntimeException("Unable to ensure default webpage for form {$form_id}");
		}

		if (!is_array($existing_page)) {
			$this->markSkeletonOwnedResource($page_id);
		}

		return $page_id;
	}

	private function ensureDefaultWidgetPage(string $widget_name): int
	{
		$path_data = $this->getDefaultPathDataForWidget($widget_name);
		$existing_page = ResourceTreeHandler::getResourceTreeEntryData($path_data['path'], $path_data['resource_name'], $this->_siteContext);

		if (is_array($existing_page) && !$this->isSkeletonOwnedResource((int) $existing_page['node_id'])) {
			return (int) $existing_page['node_id'];
		}

		$page_id = ResourceTypeWebpage::ensureDefaultWebpageWithWidget($widget_name);

		if ($page_id === false) {
			throw new RuntimeException("Unable to ensure default webpage for widget {$widget_name}");
		}

		if (!is_array($existing_page)) {
			$this->markSkeletonOwnedResource($page_id);
		}

		return $page_id;
	}

	/**
	 * @return array{path: string, resource_name: string, layout: string}
	 */
	private function getDefaultPathDataForForm(string $form_id): array
	{
		$form_class_name = 'FormType' . $form_id;

		if (!class_exists($form_class_name) || !is_subclass_of($form_class_name, 'AbstractForm')) {
			throw new RuntimeException("Requested form class {$form_class_name} does not exist or does not implement AbstractForm.");
		}

		$path_data = $form_class_name::getDefaultPathForCreation();

		return $this->normalizeDefaultPathData($path_data, "form {$form_id}");
	}

	/**
	 * @return array{path: string, resource_name: string, layout: string}
	 */
	private function getDefaultPathDataForWidget(string $widget_name): array
	{
		$widget_class_name = 'Widget' . ucwords($widget_name);

		if (!class_exists($widget_class_name) || !is_subclass_of($widget_class_name, 'AbstractWidget')) {
			throw new RuntimeException("Requested widget class {$widget_class_name} does not exist or does not implement AbstractWidget.");
		}

		$path_data = $widget_class_name::getDefaultPathForCreation();

		return $this->normalizeDefaultPathData($path_data, "widget {$widget_name}");
	}

	/**
	 * @param mixed $path_data
	 * @return array{path: string, resource_name: string, layout: string}
	 */
	private function normalizeDefaultPathData(mixed $path_data, string $label): array
	{
		if (!is_array($path_data)
			|| !isset($path_data['path'], $path_data['resource_name'], $path_data['layout'])
			|| !is_string($path_data['path'])
			|| !is_string($path_data['resource_name'])
			|| !is_string($path_data['layout'])
		) {
			throw new RuntimeException("Bad default path data for {$label}.");
		}

		return [
			'path' => $path_data['path'],
			'resource_name' => $path_data['resource_name'],
			'layout' => $path_data['layout'],
		];
	}

	/**
	 * @return array{resource_id: int, created: bool}
	 */
	private function ensureBootstrapFolder(string $path): array
	{
		$path_data = CmsPathHelper::splitFolderPath($path);
		$folder = $path_data['normalized_path'] === '/'
			? ResourceTreeHandler::getResourceTreeEntryDataById(ResourceTreeHandler::getDomainRoot($this->_siteContext) ?? 0)
			: ResourceTreeHandler::getResourceTreeEntryData($path_data['parent_path'], $path_data['resource_name'], $this->_siteContext);

		if (is_array($folder)) {
			return [
				'resource_id' => (int) $folder['node_id'],
				'created' => false,
			];
		}

		$folder_id = ResourceTreeHandler::createFolderFromPath($path, $this->_siteContext);

		if (is_int($folder_id) && $folder_id > 0) {
			$this->markSkeletonOwnedResource($folder_id);

			return [
				'resource_id' => $folder_id,
				'created' => true,
			];
		}

		throw new RuntimeException("Unable to create folder {$path}");
	}

	/**
	 * @return array{resource_id: int, created: bool}
	 */
	private function ensureBootstrapWebpage(string $path, string $resource_name, string $layout): array
	{
		$page = ResourceTreeHandler::getResourceTreeEntryData($path, $resource_name, $this->_siteContext);

		if ($page !== null) {
			return [
				'resource_id' => (int) $page['node_id'],
				'created' => false,
			];
		}

		$page_id = ResourceTreeHandler::createResourceTreeEntryFromPath($path, $resource_name, 'webpage', $layout, $this->_siteContext);

		if (is_int($page_id) && $page_id > 0) {
			$this->markSkeletonOwnedResource($page_id);

			return [
				'resource_id' => $page_id,
				'created' => true,
			];
		}

		$existing_page_id = $this->findExistingWebpageByUniquePath($path, $resource_name);

		if (is_int($existing_page_id) && $existing_page_id > 0) {
			return [
				'resource_id' => $existing_page_id,
				'created' => false,
			];
		}

		throw new RuntimeException("Unable to create webpage {$path}{$resource_name}");
	}

	private function canManageBootstrapResource(int $resource_id, bool $created): bool
	{
		return $created || $this->isSkeletonOwnedResource($resource_id);
	}

	private function isSkeletonOwnedResource(int $resource_id): bool
	{
		$attributes = AttributeHandler::getAttributes(
			new AttributeResourceIdentifier(ResourceNames::RESOURCE_DATA, (string) $resource_id)
		);

		return ($attributes[self::BOOTSTRAP_OWNER_ATTRIBUTE] ?? null) === self::BOOTSTRAP_OWNER;
	}

	private function markSkeletonOwnedResource(int $resource_id): void
	{
		AttributeHandler::addAttribute(
			new AttributeResourceIdentifier(ResourceNames::RESOURCE_DATA, (string) $resource_id),
			[self::BOOTSTRAP_OWNER_ATTRIBUTE => self::BOOTSTRAP_OWNER]
		);
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
			['is_system_group' => 1, 'title' => self::USERGROUP_EVERYONE, 'description' => 'Everyone', '_' => [
				['is_system_group' => 1, 'title' => self::USERGROUP_LOGGED_IN, 'description' => 'Logged in users'],
				['is_system_group' => 0, 'title' => self::USERGROUP_ADMINISTRATORS, 'description' => 'Administrators'],
				['is_system_group' => 0, 'title' => self::USERGROUP_DEVELOPERS, 'description' => 'Developers'],
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
		$existing = $this->findExistingUsergroup($title);

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

		if ((string) ($existing['title'] ?? '') !== $title) {
			$save_data['title'] = $title;
		}

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
		$usergroup_id = $this->getUsergroupIdByTitle($title);
		$role_row = DbHelper::selectOne('roles_tree', ['role' => $role], '', 'node_id');

		if (!is_array($role_row)) {
			throw new RuntimeException("Unable to map role {$role} to usergroup {$title}");
		}

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
		$usergroup_id = $this->getUsergroupIdByTitle($title);

		if (!Usergroups::checkUserIsAssigned($usergroup_id, $user_id) && !Usergroups::assignToUser($usergroup_id, $user_id)) {
			throw new RuntimeException("Unable to assign user {$user_id} to usergroup {$title}");
		}
	}

	private function ensureAdminAclBaseline(int $admin_folder_id): void
	{
		ResourceAcl::setInheritance($admin_folder_id, false);

		$administrators_id = $this->getUsergroupIdByTitle(self::USERGROUP_ADMINISTRATORS);

		$this->ensureUsergroupAcl($administrators_id, $admin_folder_id, [
			'allow_view' => 1,
			'allow_list' => 1,
		], self::USERGROUP_ADMINISTRATORS);
	}

	private function ensureLoginAclBaseline(int $page_id): void
	{
		ResourceAcl::setInheritance($page_id, false);

		$everyone_id = $this->getUsergroupIdByTitle(self::USERGROUP_EVERYONE);
		$administrators_id = $this->getUsergroupIdByTitle(self::USERGROUP_ADMINISTRATORS);

		$this->ensureUsergroupAcl($everyone_id, $page_id, [
			'allow_view' => 1,
			'allow_list' => 1,
		], self::USERGROUP_EVERYONE);

		$this->ensureUsergroupAcl($administrators_id, $page_id, [
			'allow_edit' => 1,
		], self::USERGROUP_ADMINISTRATORS);
	}

	private function getUsergroupIdByTitle(string $title): int
	{
		$existing = $this->findExistingUsergroup($title);
		$usergroup_id = is_array($existing) ? (int) $existing['node_id'] : 0;

		if ($usergroup_id <= 0) {
			throw new RuntimeException("Usergroup not found: {$title}");
		}

		return $usergroup_id;
	}

	/**
	 * @return array<string, int|float|string|bool>|null
	 */
	private function findExistingUsergroup(string $title): ?array
	{
		$system_usergroup_id = self::STABLE_SYSTEM_USERGROUP_IDS[$title] ?? null;

		if (is_int($system_usergroup_id)) {
			$existing = DbHelper::selectOne('usergroups_tree', ['node_id' => $system_usergroup_id], '', 'node_id,parent_id,title,description,is_system_group');

			return is_array($existing) ? $existing : null;
		}

		$existing = DbHelper::selectOne('usergroups_tree', ['title' => $title], '', 'node_id,parent_id,title,description,is_system_group');

		if (is_array($existing)) {
			return $existing;
		}

		$bootstrap_role = self::BOOTSTRAP_USERGROUP_ROLES[$title] ?? null;

		if (!is_string($bootstrap_role)) {
			return null;
		}

		return DbHelper::selectOneFromQuery(
			"
			SELECT
				ugt.node_id,
				ugt.parent_id,
				ugt.title,
				ugt.description,
				ugt.is_system_group
			FROM
				usergroups_tree ugt
			INNER JOIN usergroups_roles_mapping ugrm
				ON ugrm.usergroup_id = ugt.node_id
			INNER JOIN roles_tree rt
				ON rt.node_id = ugrm.role_id
			WHERE
				rt.role = ?
			ORDER BY
				ugt.node_id ASC
			LIMIT 1
			",
			[$bootstrap_role]
		);
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
