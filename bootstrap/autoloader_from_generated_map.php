<?php

class AutoloaderFromGeneratedMap
{
	/** @var array<string, string> */
	private static array $_autoload_map = [];

	private static bool $_initialized = false;

	public static function init(): void
	{
		if (self::$_initialized) {
			return;
		}

		self::$_initialized = true;

		require_once DEPLOY_ROOT . 'generated/__autoload__.php';
		self::$_autoload_map = AutoloaderGeneratedMap::getAutoloadMap();
	}

	public static function loadClass(string $name): void
	{
		if (!isset(self::$_autoload_map[$name])) {
			// Defer to other registered autoloaders if ours did not find the class.
			return;
		}

		$full_path = self::resolveStoredPath(self::$_autoload_map[$name]);

		// May be out of sync after moving files around, so checking for existence first,
		// effectively falling back to the failsafe autoloader.
		if (file_exists($full_path)) {
			require_once $full_path;
		}
	}

	public static function autoloaderClassExists(string $name): bool
	{
		if (isset(self::$_autoload_map[$name])) {
			return true;
		} else {
			return AutoloaderFailsafe::autoloaderClassExists($name);
		}
	}

	/**
	 * Retrieves a filtered list of autoload entries.
	 *
	 * This method filters the `$_autoload` array based on the provided filter string.
	 * It returns an array of strings where each string is an autoload entry with the
	 * filter string removed from the beginning.
	 *
	 * @param string $filter The filter string to match at the beginning of autoload entries.
	 * @return string[] An array of autoload entries with the filter string removed.
	 */
	public static function getFilteredList(string $filter): array
	{
		$return = [];

		foreach (self::$_autoload_map as $autoload => $path) {
			if (mb_strpos($autoload, $filter) === 0) {
				$return[] = str_replace($filter, '', $autoload);
			}
		}

		return $return;
	}

	/**
	 * This method is used when generating XDEBUG_PROFILE files, to avoid polluting them with autoloader calls.
	 *
	 * @return void
	 */
	public static function preloadAll(): void
	{
		foreach (self::$_autoload_map as $className => $path) {
			$fullPath = self::resolveStoredPath($path);
			require_once $fullPath;
		}
	}

	public static function register_mapped_autoloader(): void
	{
		spl_autoload_register(function (string $fullyQualifiedClassName) {
			self::init();

			$className = basename(str_replace('\\', '/', $fullyQualifiedClassName));
			AutoloaderFromGeneratedMap::loadClass($className);
		});
	}

	public static function reset(): void
	{
		self::$_autoload_map = [];
		self::$_initialized = false;
	}

	private static function resolveStoredPath(string $path): string
	{
		if (str_starts_with($path, '/')) {
			return $path;
		}

		return DEPLOY_ROOT . ltrim($path, '/');
	}
}
