<?php

/**
 * Fixture for the usergroups_roles_mapping table.
 *
 * Uses @table.ref syntax for foreign keys:
 * - @usergroups_tree.{title} for usergroup_id
 * - @roles_tree.{role} for role_id
 */
class FixtureUsergroupsRolesMapping extends AbstractFixture
{
	public function getTableName(): string
	{
		return 'usergroups_roles_mapping';
	}

	/**
	 * @return list<class-string<AbstractFixture>>
	 */
	public function getDependencies(): array
	{
		return [
			FixtureUsergroupsTree::class,
			FixtureRolesTree::class,
		];
	}

	/**
	 * @return list<array{
	 *     usergroup_id: string,
	 *     role_id: string
	 * }>
	 */
	public function getData(): array
	{
		return [
			// Administrators group has system_administrator role
			['usergroup_id' => '@usergroups_tree.Rendszergazdák', 'role_id' => '@roles_tree.system_administrator'],
			// Developers group has system_developer role
			['usergroup_id' => '@usergroups_tree.Fejlesztők', 'role_id' => '@roles_tree.system_developer'],
		];
	}
}
