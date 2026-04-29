<?php

declare(strict_types=1);

class SlidingGallery
{
	public const string RESOURCENAME = '_sliding_gallery';

	private const array VALID_MIMES = ['image/jpeg', 'image/png', 'image/gif'];

	/**
	 * @param array<string, mixed> $savedata
	 */
	public static function saveSettings(array $savedata): int
	{
		return AttributeHandler::addAttribute(new AttributeResourceIdentifier(self::RESOURCENAME), $savedata);
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function getSettings(): array
	{
		return AttributeHandler::getAttributes(new AttributeResourceIdentifier(self::RESOURCENAME));
	}

	public static function getFolderId(): ?int
	{
		$data = self::getSettings();
		$folder_id = (int) ($data['sliding_gallery_folder_id'] ?? 0);

		return $folder_id > 0 ? $folder_id : null;
	}

	/**
	 * @return list<array<string, mixed>>
	 */
	public static function getMediaList(int $folder_id, string $predefined_name = 'sliding_gallery'): array
	{
		static $group_id = 0;

		++$group_id;

		$return = [];

		foreach (ResourceTreeHandler::getResourceTree($folder_id) as $resource_data) {
			if (($resource_data['node_type'] ?? null) !== 'file') {
				continue;
			}

			$attributes = ResourceTypeFile::getExtradata((int) $resource_data['node_id']);
			$mime = (string) ($attributes['mime'] ?? '');
			$file_id = (int) ($attributes['file_id'] ?? 0);

			if (!in_array($mime, self::VALID_MIMES, true) || $file_id <= 0) {
				continue;
			}

			$return[] = [
				'predefined' => PredefinedImageHandler::getImageData((int) $resource_data['node_id'], $file_id, $predefined_name),
				'full' => Url::getSeoUrl((int) $resource_data['node_id'], false),
				'group' => $group_id,
				'big' => PredefinedImageHandler::getImageData((int) $resource_data['node_id'], $file_id, 'big'),
			];
		}

		return $return;
	}
}
