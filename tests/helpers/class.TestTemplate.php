<?php

declare(strict_types=1);

final class TestTemplate extends Template
{
	/** @inheritDoc */
	protected static function lookupTemplateRenderer(string $templateName): string
	{
		return TestTemplateList::getRendererForTemplate($templateName);
	}

	/** @inheritDoc */
	protected static function lookupHasTemplate(string $templateName): bool
	{
		return TestTemplateList::hasTemplate($templateName);
	}

	/** @inheritDoc */
	public function getTemplatePath(string $templateName): string
	{
		return TestTemplateList::getPathForTemplate($templateName);
	}
}
