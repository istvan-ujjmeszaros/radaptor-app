<?php

declare(strict_types=1);

class ImagePredefinedBig extends PredefinedImageHandler
{
	public function getPathForManipulatedImage(): string
	{
		return $this->_originalPath
			|> ImageManipulator::fit(maxWidth: 800, maxHeight: 600, outputFormat: 'jpg', quality: 60, enableZooming: true)
			|> ImageManipulator::cache(cacheSubdirectoryName: 'big');
	}
}
