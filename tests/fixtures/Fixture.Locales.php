<?php

/**
 * Fixture for the locales table.
 */
class FixtureLocales extends AbstractFixture
{
	public function getTableName(): string
	{
		return 'locales';
	}

	public function getReferenceBy(): string
	{
		return 'locale';
	}

	/**
	 * @return list<array{
	 *     locale: string,
	 *     label: string,
	 *     native_label: string,
	 *     is_enabled: int,
	 *     sort_order: int
	 * }>
	 */
	public function getData(): array
	{
		return [
			[
				'locale' => 'en-US',
				'label' => 'English (United States) (en-US)',
				'native_label' => 'English (United States)',
				'is_enabled' => 1,
				'sort_order' => 10,
			],
			[
				'locale' => 'hu-HU',
				'label' => 'Magyar (Magyarország) (hu-HU)',
				'native_label' => 'Magyar (Magyarország)',
				'is_enabled' => 1,
				'sort_order' => 20,
			],
		];
	}
}
