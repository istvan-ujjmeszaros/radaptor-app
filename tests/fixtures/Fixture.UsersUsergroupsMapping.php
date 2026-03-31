<?php

/**
 * Fixture for the users_usergroups_mapping table.
 *
 * Uses @table.ref syntax for foreign keys:
 * - @users.{username} for user_id
 * - @usergroups_tree.{title} for usergroup_id
 */
class FixtureUsersUsergroupsMapping extends AbstractFixture
{
	public function getTableName(): string
	{
		return 'users_usergroups_mapping';
	}

	/**
	 * @return list<class-string<AbstractFixture>>
	 */
	public function getDependencies(): array
	{
		return [
			FixtureUsers::class,
			FixtureUsergroupsTree::class,
		];
	}

	/**
	 * @return list<array{
	 *     user_id: string,
	 *     usergroup_id: string
	 * }>
	 */
	public function getData(): array
	{
		return [
			// admin_developer is in administrators and developers groups
			['user_id' => '@users.admin_developer', 'usergroup_id' => '@usergroups_tree.Rendszergazdák'],
			['user_id' => '@users.admin_developer', 'usergroup_id' => '@usergroups_tree.Fejlesztők'],
			// user_noroles is in administrators group
			['user_id' => '@users.user_noroles', 'usergroup_id' => '@usergroups_tree.Rendszergazdák'],
			// admin_inactive is in developers group
			['user_id' => '@users.admin_inactive', 'usergroup_id' => '@usergroups_tree.Fejlesztők'],
			// E2E users in both groups
			['user_id' => '@users.developer_user_new_theme', 'usergroup_id' => '@usergroups_tree.Rendszergazdák'],
			['user_id' => '@users.developer_user_new_theme', 'usergroup_id' => '@usergroups_tree.Fejlesztők'],
			['user_id' => '@users.developer_user_old_theme', 'usergroup_id' => '@usergroups_tree.Rendszergazdák'],
			['user_id' => '@users.developer_user_old_theme', 'usergroup_id' => '@usergroups_tree.Fejlesztők'],
		];
	}
}
