<?php

/**
 * Layout component for user menu in the sidebar footer.
 *
 * Displays username with dropdown containing logout link.
 */
class LayoutComponentUserMenu extends AbstractLayoutComponent
{
	public const string ID = 'user_menu';

	public function buildTree(): array
	{
		return $this->createComponentTree('userMenu', [], strings: self::buildStrings());
	}

	/**
	 * @return array<string, string>
	 */
	public static function buildStrings(): array
	{
		return [
			'common.logout' => t('common.logout'),
		];
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
