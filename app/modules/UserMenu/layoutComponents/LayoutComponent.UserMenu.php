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
		$current_locale = Kernel::getLocale();
		$available_locales = I18nRuntime::getAvailableLocales();

		return $this->createComponentTree('userMenu', [
			'current_locale' => $current_locale,
			'current_locale_label' => LocaleRegistry::getDisplayLabel($current_locale),
			'available_locales' => $available_locales,
			'locale_update_url' => Url::getUrl('user.set-locale', [
				'referer' => Url::getCurrentUrlForReferer(),
			]),
		], strings: self::buildStrings());
	}

	/**
	 * @return array<string, string>
	 */
	public static function buildStrings(): array
	{
		return [
			'common.logout' => t('common.logout'),
			'user.locale.current_label' => t('user.locale.current_label'),
			'user.locale.menu_label' => t('user.locale.menu_label'),
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
