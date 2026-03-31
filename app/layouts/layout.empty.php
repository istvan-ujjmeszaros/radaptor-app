<?php

class LayoutTypePublicEmpty extends AbstractLayoutType
{
	public const string ID = 'public_empty';
	public const bool VISIBILITY = true;

	private static array $_SLOTS = ['content', ];

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
		return $this->createLayoutTree('layout_public_empty', slots: [
			'content' => $slot_trees['content'] ?? [],
		]);
	}
}
