<?php

/**
 * Fixture for the roles_tree table.
 *
 * Uses nested structure with '_' key for children.
 * The loader automatically calculates lft, rgt, parent_id.
 *
 * References: @roles_tree.{role}
 * Example: @roles_tree.system_administrator
 */
class FixtureRolesTree extends AbstractFixture
{
	public function getTableName(): string
	{
		return 'roles_tree';
	}

	public function getReferenceBy(): string
	{
		return 'role';
	}

	/**
	 * @return list<array{
	 *     title: string,
	 *     description: ?string,
	 *     role: ?string,
	 *     _?: list<array<string, mixed>>
	 * }>
	 */
	public function getData(): array
	{
		return [
			['title' => 'Fejlesztő', 'description' => 'Developer', 'role' => 'system_developer', '_' => [
				['title' => 'Felhasználói csoport adminisztrátor', 'description' => 'Usergroup admin', 'role' => 'usergroups_admin', '_' => [
					['title' => 'Felhasználói csoport szerep adminisztrátor', 'description' => 'Usergroup role admin', 'role' => 'usergroups_role_admin'],
				]],
				['title' => 'Szerep adminisztrátor', 'description' => 'Role admin', 'role' => 'roles_admin', '_' => [
					['title' => 'Szerep lista megtekintő', 'description' => 'Role spectator', 'role' => 'roles_viewer'],
				]],
				['title' => 'Domain adminisztrátor', 'description' => 'Domain admin', 'role' => 'domains_admin'],
				['title' => 'Weboldal hozzáférés adminisztrátor', 'description' => 'Resource ACL admin', 'role' => 'acl_admin'],
			]],
			['title' => 'Rendszer adminisztrátor', 'description' => 'System administrator', 'role' => 'system_administrator', '_' => [
				['title' => 'Fordító', 'description' => 'Manage i18n translations in the workbench and import/export flows', 'role' => 'i18n_translator'],
				['title' => 'Szerkeszthető szöveg adminisztrátor', 'description' => 'Richtext admin', 'role' => 'richtext_administrator'],
				['title' => 'Blog adminisztrátor', 'description' => 'Blog admin', 'role' => 'blog_admin'],
				['title' => 'E-mail adminisztrátor', 'description' => 'Email administration and sending', 'role' => 'emails_admin'],
				['title' => 'Weboldal hozzáférés megtekintő', 'description' => 'Resource ACL viewer', 'role' => 'acl_viewer'],
				['title' => 'Felhasználó adminisztráció', 'description' => 'User admin', 'role' => 'users_admin', '_' => [
					['title' => 'Felhasználó szerep adminisztrátor', 'description' => 'User role admin', 'role' => 'users_role_admin'],
					['title' => 'Felhasználó csoport hozzárendelő adminisztrátor', 'description' => 'User usergroup admin', 'role' => 'users_usergroup_admin'],
				]],
				['title' => 'Fájl adminisztrátor', 'description' => 'File uploader', 'role' => 'files_admin'],
				['title' => 'Tartalom szerkesztő', 'description' => 'Content editor', 'role' => 'content_admin'],
				['title' => 'Idő bejegyzés adminisztrátor', 'description' => 'Timetracker admin', 'role' => 'timetracker_administrator', '_' => [
					['title' => 'Idő bejegyzés lista megtekintő', 'description' => 'Timetracker spectator', 'role' => 'timetracker_viewer'],
				]],
			]],
		];
	}
}
