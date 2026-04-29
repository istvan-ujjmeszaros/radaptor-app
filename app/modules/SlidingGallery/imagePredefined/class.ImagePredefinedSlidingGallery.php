<?php

declare(strict_types=1);

class ImagePredefinedSlidingGallery extends PredefinedImageHandler
{
	public function getPathForManipulatedImage(): string
	{
		return $this->_originalPath
			|> ImageManipulator::crop(maxWidth: 200, maxHeight: 120, outputFormat: 'jpg', quality: 60, enableZooming: true)
			|> ImageManipulator::cache(cacheSubdirectoryName: 'sliding_gallery');
	}
}
