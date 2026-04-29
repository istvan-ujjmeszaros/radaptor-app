<?php

declare(strict_types=1);

class LayoutComponentSlidingGallery extends AbstractLayoutComponent
{
	public const string ID = 'sliding_gallery';

	public function buildTree(): array
	{
		$settings = SlidingGallery::getSettings();
		$folder_id = SlidingGallery::getFolderId();
		$media_list = $folder_id !== null ? SlidingGallery::getMediaList($folder_id, 'sliding_gallery') : [];

		if (filter_var($settings['shuffle'] ?? false, FILTER_VALIDATE_BOOL)) {
			shuffle($media_list);
		}

		return $this->createComponentTree('slidingGallery', [
			'mediaList' => $media_list,
		]);
	}

	public static function getLayoutComponentName(): string
	{
		return 'Sliding gallery';
	}

	public static function getLayoutComponentDescription(): string
	{
		return 'Sliding gallery';
	}
}
