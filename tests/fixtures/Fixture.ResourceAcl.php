<?php

/**
 * Fixture for the resource_acl table.
 */
class FixtureResourceAcl extends AbstractFixture
{
	public function getTableName(): string
	{
		return 'resource_acl';
	}

	/**
	 * @return list<array<string, mixed>>
	 */
	public function getData(): array
	{
		return [
			[
				'acl_id' => 2,
				'resource_id' => 2,
				'subject_type' => 'usergroup',
				'subject_id' => 3,
				'allow_view' => 1,
				'allow_edit' => 0,
				'allow_delete' => 0,
				'allow_publish' => 0,
				'allow_list' => 1,
				'allow_create' => 0,
			],
			[
				'acl_id' => 3,
				'resource_id' => 2,
				'subject_type' => 'usergroup',
				'subject_id' => 4,
				'allow_view' => 1,
				'allow_edit' => 0,
				'allow_delete' => 0,
				'allow_publish' => 0,
				'allow_list' => 1,
				'allow_create' => 0,
			],
			[
				'acl_id' => 4,
				'resource_id' => 1,
				'subject_type' => 'usergroup',
				'subject_id' => 3,
				'allow_view' => 0,
				'allow_edit' => 1,
				'allow_delete' => 0,
				'allow_publish' => 0,
				'allow_list' => 0,
				'allow_create' => 0,
			],
			[
				'acl_id' => 5,
				'resource_id' => 1,
				'subject_type' => 'usergroup',
				'subject_id' => 2,
				'allow_view' => 1,
				'allow_edit' => 0,
				'allow_delete' => 0,
				'allow_publish' => 0,
				'allow_list' => 1,
				'allow_create' => 0,
			],
			[
				'acl_id' => 6,
				'resource_id' => 21,
				'subject_type' => 'usergroup',
				'subject_id' => 3,
				'allow_view' => 1,
				'allow_edit' => 0,
				'allow_delete' => 0,
				'allow_publish' => 0,
				'allow_list' => 1,
				'allow_create' => 0,
			],
			[
				'acl_id' => 7,
				'resource_id' => 126,
				'subject_type' => 'usergroup',
				'subject_id' => 1,
				'allow_view' => 1,
				'allow_edit' => 0,
				'allow_delete' => 0,
				'allow_publish' => 0,
				'allow_list' => 0,
				'allow_create' => 0,
			],
			[
				'acl_id' => 12,
				'resource_id' => 36,
				'subject_type' => 'user',
				'subject_id' => 1,
				'allow_view' => 1,
				'allow_edit' => 0,
				'allow_delete' => 0,
				'allow_publish' => 0,
				'allow_list' => 1,
				'allow_create' => 1,
			],
			[
				'acl_id' => 21,
				'resource_id' => 8,
				'subject_type' => 'user',
				'subject_id' => 1,
				'allow_view' => 1,
				'allow_edit' => 0,
				'allow_delete' => 0,
				'allow_publish' => 0,
				'allow_list' => 1,
				'allow_create' => 1,
			],
			[
				'acl_id' => 24,
				'resource_id' => 127,
				'subject_type' => 'user',
				'subject_id' => 1,
				'allow_view' => 0,
				'allow_edit' => 0,
				'allow_delete' => 0,
				'allow_publish' => 0,
				'allow_list' => 0,
				'allow_create' => 0,
			],
		];
	}
}
