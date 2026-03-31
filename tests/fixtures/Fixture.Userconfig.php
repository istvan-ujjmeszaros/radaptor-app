<?php

/**
 * Fixture for the userconfig table.
 *
 * Uses @table.ref syntax for foreign keys:
 * - @users.{username} for user_id
 */
class FixtureUserconfig extends AbstractFixture
{
	public function getTableName(): string
	{
		return 'config_user';
	}

	/**
	 * @return list<class-string<AbstractFixture>>
	 */
	public function getDependencies(): array
	{
		return [
			FixtureUsers::class,
		];
	}

	/**
	 * @return list<array{
	 *     user_id: string,
	 *     config_key: string,
	 *     value: string
	 * }>
	 */
	public function getData(): array
	{
		// Config key format: "themeoverride:{current-site-theme}" => "{override-theme-for-this-user}"
		return [
			// developer_user_old_theme: when the site uses RadaptorPortalAdmin, switch them to SoAdmin
			[
				'user_id' => '@users.developer_user_old_theme',
				'config_key' => 'themeoverride:RadaptorPortalAdmin',
				'value' => 'SoAdmin',
			],
			// developer_user_new_theme: when the site uses SoAdmin, switch them to RadaptorPortalAdmin
			[
				'user_id' => '@users.developer_user_new_theme',
				'config_key' => 'themeoverride:SoAdmin',
				'value' => 'RadaptorPortalAdmin',
			],
		];
	}
}
