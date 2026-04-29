<?php

class LayoutTypePublicDefault extends AbstractLayoutType
{
	public const string ID = 'public_default';
	public const bool VISIBILITY = true;

	private static array $_SLOTS = ['content'];

	public static function getName(): string
	{
		return t('layout.' . self::ID . '.name');
	}

	public static function getDescription(): string
	{
		return t('layout.' . self::ID . '.description');
	}

	public static function getListVisibility(): bool
	{
		return Roles::hasRole(RoleList::ROLE_SYSTEM_DEVELOPER);
	}

	public static function getSlots(): array
	{
		return self::$_SLOTS;
	}

	public function buildTree(iTreeBuildContext $webpage_composer, array $slot_trees, array $build_context = []): array
	{
		$main_menu = new LayoutComponentMainMenu($webpage_composer);
		$sliding_gallery = new LayoutComponentSlidingGallery($webpage_composer);

		return $this->createLayoutTree('layout_public_default', [
			'lang' => 'de-at',
		], slots: [
			'main_menu' => [$main_menu->buildTree()],
			'content' => $slot_trees['content'] ?? [],
			'sliding_gallery' => [$sliding_gallery->buildTree()],
		]);
	}
}
