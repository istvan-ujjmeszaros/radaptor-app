<?php

class TestTemplateList{

	/**
	 * Template name to relative path mapping.
	 * @var array<string, string>
	 */
	protected static array $_templateList = [
	];

	public static function hasTemplate(string $templateName): bool
	{
		return isset(self::$_templateList[$templateName]);
	}

	public static function getPathForTemplate(string $templateName): string
	{
		return self::$_templateList[$templateName] ?? '';
	}

	/**
	 * @return array<string, string>
	 */
	public static function getTemplates(): array
	{
		return self::$_templateList;
	}

	/**
	 * Template name to renderer class mapping.
	 * @var array<string, class-string<iTemplateRenderer>>
	 */
	protected static array $_templateRenderers = [
	];

	/**
	 * Get the renderer class for a template.
	 *
	 * @param string $templateName Template name
	 * @return class-string<iTemplateRenderer> Renderer class name
	 */
	public static function getRendererForTemplate(string $templateName): string
	{
		return self::$_templateRenderers[$templateName] ?? 'TemplateRendererPhp';
	}
}
