<?php

/**
 * Layout component for hardcoded admin sidebar navigation links.
 *
 * Renders links like Felhasználók, Szerepek, etc. that are always visible
 * in the admin sidebar (with role-based visibility).
 */
class LayoutComponentSideMenuAdmin extends AbstractLayoutComponent
{
	public const string ID = 'side_menu_admin';

	/**
	 * @return array<string, string>
	 */
	public static function buildStrings(): array
	{
		return [
			'admin.menu.section.content' => t('admin.menu.section.content'),
			'admin.menu.blog' => t('admin.menu.blog'),
			'admin.menu.section.administration' => t('admin.menu.section.administration'),
			'user.list.title' => t('user.list.title'),
			'admin.menu.usergroups' => t('admin.menu.usergroups'),
			'admin.menu.roles' => t('admin.menu.roles'),
			'admin.menu.section.configuration' => t('admin.menu.section.configuration'),
			'admin.menu.resource_tree' => t('admin.menu.resource_tree'),
			'admin.menu.translations' => t('admin.menu.translations'),
			'admin.menu.import_export' => t('admin.menu.import_export'),
			'admin.menu.admin_menu' => t('admin.menu.admin_menu'),
			'admin.menu.theme_selector' => t('admin.menu.theme_selector'),
			'admin.menu.section.developer_tools' => t('admin.menu.section.developer_tools'),
			'admin.menu.widget_preview' => t('admin.menu.widget_preview'),
			'admin.menu.phpinfo' => t('admin.menu.phpinfo'),
			'admin.menu.template_engines' => t('admin.menu.template_engines'),
			'admin.menu.cli_runner' => t('admin.menu.cli_runner'),
		];
	}

	public function buildTree(): array
	{
		return $this->createComponentTree('sideMenuAdmin', [], strings: self::buildStrings());
	}

	public static function getLayoutComponentName(): string
	{
		return t('layout.' . self::ID . '.name');
	}

	public static function getLayoutComponentDescription(): string
	{
		return t('layout.' . self::ID . '.description');
	}
}
