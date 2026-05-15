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
		$autoload_map_path = DEPLOY_ROOT . 'generated/__autoload__.php';

		if (!is_file($autoload_map_path)) {
			self::$_autoload_map = [];

			return;
		}

		require_once $autoload_map_path;

		if (class_exists('AutoloaderGeneratedMap', false)) {
			self::$_autoload_map = AutoloaderGeneratedMap::getAutoloadMap();
		}
	}

	public static function loadClass(string $name): void
	{
		if (!isset(self::$_autoload_map[$name])) {
			// Defer to other registered autoloaders if ours did not find the class.
			return;
		}

		$full_path = self::resolveRuntimePath(self::$_autoload_map[$name]);

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
		$seen = [];

		foreach (self::$_autoload_map as $autoload => $path) {
			if (mb_strpos($autoload, $filter) === 0) {
				$short_name = str_replace($filter, '', $autoload);

				if (!isset($seen[$short_name])) {
					$return[] = $short_name;
					$seen[$short_name] = true;
				}
			}
		}

		foreach (AutoloaderFailsafe::getAutoloadMap() as $autoload => $path) {
			$short_class_name = basename(str_replace('\\', '/', $autoload));

			if (mb_strpos($short_class_name, $filter) !== 0) {
				continue;
			}

			$short_name = str_replace($filter, '', $short_class_name);

			if (!isset($seen[$short_name])) {
				$return[] = $short_name;
				$seen[$short_name] = true;
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
			$fullPath = self::resolveRuntimePath($path);
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

	private static function resolveRuntimePath(string $stored_path): string
	{
		$resolved_path = self::resolveStoredPath($stored_path);
		$remapped_path = self::remapManagedPackagePath($resolved_path);

		return $remapped_path ?? $resolved_path;
	}

	private static function remapManagedPackagePath(string $path): ?string
	{
		$normalized_path = self::normalizePath($path);
		$normalized_root = self::normalizePath(DEPLOY_ROOT);
		$dev_root = self::getDevRoot();

		if (str_starts_with($normalized_path, $normalized_root . '/')) {
			$relative = ltrim(substr($normalized_path, strlen($normalized_root)), '/');
			$segments = explode('/', $relative);

			if ($segments[0] === 'packages' && count($segments) >= 5) {
				$type = $segments[2] === 'core' ? 'core' : ($segments[2] === 'themes' ? 'theme' : null);
				$id = $segments[3] ?? null;
				$relative_inside_package = implode('/', array_slice($segments, 4));
			} else {
				return null;
			}
		} elseif ($dev_root !== null && str_starts_with($normalized_path, $dev_root . '/')) {
			$relative = ltrim(substr($normalized_path, strlen($dev_root)), '/');
			$segments = explode('/', $relative);

			if ($segments[0] === 'core' && count($segments) >= 3) {
				$type = 'core';
				$id = $segments[1] ?? null;
				$relative_inside_package = implode('/', array_slice($segments, 2));
			} elseif ($segments[0] === 'themes' && count($segments) >= 3) {
				$type = 'theme';
				$id = $segments[1] ?? null;
				$relative_inside_package = implode('/', array_slice($segments, 2));
			} else {
				return null;
			}
		} else {
			return null;
		}

		if (!is_string($type) || !is_string($id) || $relative_inside_package === '') {
			return null;
		}

		$active_root = PackagePathHelper::getPackageRoot($type, $id);

		if (!is_string($active_root) || $active_root === '') {
			return null;
		}

		$candidate = rtrim($active_root, '/') . '/' . $relative_inside_package;

		return file_exists($candidate) ? $candidate : null;
	}

	private static function getDevRoot(): ?string
	{
		$configured = trim((string) getenv('RADAPTOR_DEV_ROOT'));

		if ($configured !== '') {
			return self::normalizePath($configured);
		}

		return null;
	}

	private static function normalizePath(string $path): string
	{
		$path = str_replace('\\', '/', $path);
		$real = realpath($path);

		if ($real !== false) {
			return rtrim(str_replace('\\', '/', $real), '/');
		}

		return rtrim($path, '/');
	}
}
