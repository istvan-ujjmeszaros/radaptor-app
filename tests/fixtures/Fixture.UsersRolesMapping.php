<?php

/**
 * Fixture for the users_roles_mapping table.
 *
 * Uses @table.ref syntax for foreign keys:
 * - @users.{username} for user_id
 * - @roles_tree.{role} for role_id
 */
class FixtureUsersRolesMapping extends AbstractFixture
{
	public function getTableName(): string
	{
		return 'users_roles_mapping';
	}

	/**
	 * @return list<class-string<AbstractFixture>>
	 */
	public function getDependencies(): array
	{
		return [
			FixtureUsers::class,
			FixtureRolesTree::class,
		];
	}

	/**
	 * @return list<array{
	 *     user_id: string,
	 *     role_id: string
	 * }>
	 */
	public function getData(): array
	{
		return [
			// admin_developer has system_administrator and system_developer roles
			// emails_admin is inherited via system_administrator hierarchy, no direct assignment needed
			['user_id' => '@users.admin_developer', 'role_id' => '@roles_tree.system_administrator'],
			['user_id' => '@users.admin_developer', 'role_id' => '@roles_tree.system_developer'],
			// admin_inactive has system_administrator role
			['user_id' => '@users.admin_inactive', 'role_id' => '@roles_tree.system_administrator'],
			// E2E users require both admin and developer capabilities
			['user_id' => '@users.developer_user_new_theme', 'role_id' => '@roles_tree.system_administrator'],
			['user_id' => '@users.developer_user_new_theme', 'role_id' => '@roles_tree.system_developer'],
			['user_id' => '@users.developer_user_old_theme', 'role_id' => '@roles_tree.system_administrator'],
			['user_id' => '@users.developer_user_old_theme', 'role_id' => '@roles_tree.system_developer'],
		];
	}
}
