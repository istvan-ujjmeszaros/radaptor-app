<?php

/**
 * Fixture for the usergroups_tree table.
 *
 * Uses nested structure with '_' key for children.
 * The loader automatically calculates lft, rgt, parent_id.
 *
 * References: @usergroups_tree.{title}
 * Example: @usergroups_tree.Rendszergazdák
 */
class FixtureUsergroupsTree extends AbstractFixture
{
	public function getTableName(): string
	{
		return 'usergroups_tree';
	}

	public function getReferenceBy(): string
	{
		return 'title';
	}

	/**
	 * @return list<array{
	 *     is_system_group: int,
	 *     title: string,
	 *     description: ?string,
	 *     _?: list<array<string, mixed>>
	 * }>
	 */
	public function getData(): array
	{
		return [
			['is_system_group' => 1, 'title' => 'Mindenki', 'description' => 'Everyone', '_' => [
				['is_system_group' => 1, 'title' => 'Bejelentkezett felhasználók', 'description' => 'Logged in users'],
				['is_system_group' => 0, 'title' => 'Rendszergazdák', 'description' => 'Administrators'],
				['is_system_group' => 0, 'title' => 'Fejlesztők', 'description' => 'Developers'],
			]],
		];
	}
}
