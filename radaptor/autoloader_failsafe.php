<?php

foreach ([
	__DIR__ . '/radaptor-framework/classes/class.PluginVersionHelper.php' => 'PluginVersionHelper',
	__DIR__ . '/radaptor-framework/classes/class.PackageDependencyHelper.php' => 'PackageDependencyHelper',
	__DIR__ . '/radaptor-framework/classes/class.PackageTypeHelper.php' => 'PackageTypeHelper',
	__DIR__ . '/radaptor-framework/classes/class.PackageLockfile.php' => 'PackageLockfile',
	__DIR__ . '/radaptor-framework/classes/class.PackagePathHelper.php' => 'PackagePathHelper',
] as $bootstrapClassFile => $bootstrapClassName) {
	if (!class_exists($bootstrapClassName, false) && is_file($bootstrapClassFile)) {
		require_once $bootstrapClassFile;
	}
}

class AutoloaderFailsafe
{
	/** @var string[] */
	private static array $excludedPath;

	/** @var array<class-string, SplFileInfo> */
	private static array $classMap = [];

	/** @var array<class-string, int> */
	private static array $classPriorityMap = [];
	private static bool $initialized_failsafe = false;
	private static bool $fallback_header_sent = false;

	/**
	 * Recursively scan a directory for PHP files, excluding specified folders.
	 * @param string $dir The directory to scan
	 * @param list<string> $excludedFolders List of folder paths to exclude
	 * @return Generator<SplFileInfo> Generator yielding PHP file paths
	 */
	private static function scanDirectory(string $dir, array $excludedFolders): Generator
	{
		$directoryIterator = new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS);

		foreach ($directoryIterator as $pathEntry) {
			// Skipping hidden entries (both files and folders)
			if ($pathEntry->getFilename()[0] === '.') {
				continue;
			}

			// Check if the current path should be excluded
			if (in_array($pathEntry->getPathname(), $excludedFolders, true)) {
				continue;
			}

			if (PackagePathHelper::shouldSkipPath($pathEntry->getPathname())) {
				continue;
			}

			if ($pathEntry->isDir()) {
				// Recursively scan subdirectories
				yield from self::scanDirectory($pathEntry->getPathname(), $excludedFolders);
			} elseif ($pathEntry->isFile() && $pathEntry->getExtension() === 'php') {
				yield $pathEntry;
			}
		}
	}

	/**
	 * Function to get all PHP files recursively from a directory while excluding specified folders.
	 * @param list<string> $excludedFolders List of folder paths to exclude
	 * @return list<SplFileInfo> List of absolute paths to PHP files
	 */
	public static function getProjectPhpFiles(array $excludedFolders): array
	{
		/** @var list<SplFileInfo> $files */
		$files = [];

		foreach (PackagePathHelper::getScannableRoots() as $root) {
			if (!is_dir($root)) {
				continue;
			}

			foreach (self::scanDirectory($root, $excludedFolders) as $file) {
				$files[] = $file;
			}
		}

		return $files;
	}

	/**
	 * Function to get the fully qualified class name from a PHP file.
	 * @param SplFileInfo $file
	 * @return class-string|null
	 */
	public static function getClassNameFromFile(SplFileInfo $file): ?string
	{
		$real_path = $file->getRealPath();

		if ($real_path === false || $real_path === '') {
			return null;
		}

		$code = file_get_contents($real_path);

		if ($code === false) {
			return null;
		}

		$tokens = token_get_all($code);
		$className = null;
		$namespace = '';

		for ($i = 0; $i < count($tokens); $i++) {
			if ($tokens[$i][0] === T_NAMESPACE) {
				$namespace = '';

				while (++$i < count($tokens)) {
					if ($tokens[$i][0] === T_STRING) {
						$namespace .= $tokens[$i][1] . '\\';
					} elseif ($tokens[$i] === '{' || $tokens[$i] === ';') {
						break;
					}
				}
			} elseif ($tokens[$i][0] === T_CLASS || $tokens[$i][0] === T_INTERFACE || $tokens[$i][0] === T_ENUM || $tokens[$i][0] === T_TRAIT) {
				while (++$i < count($tokens)) {
					if ($tokens[$i][0] === T_STRING) {
						$className = $namespace . $tokens[$i][1];

						break;
					}
				}

				break;
			}
		}

		return $className;
	}

	public static function autoloaderClassExists(string $name): bool
	{
		// We do NOT call init() here because we do check for class existence at different places in the code on
		// purpose, like looking up if an event has a BROWSER-specific event, but some classes were being loaded at
		// that point already. If the generated autoloader didn't exist yet, then our autoloader will call init()
		// before getting here. On the other hand, if the generated autoloader exists, then that should handle the
		// existence check for the class.
		// This is a neat trick to NOT cause a performance penalty when the generated autoloader is up-to-date, but
		// this fallback method will work as expected if the generated autoloader is not up-to-date, because the
		// fallback autoloader will build the mapping before the existence check happens.
		if (
			(defined('RADAPTOR_CLI') && EventResolver::getEventnameFromCommandline() === 'BuildAutoloader')
			|| (EventResolver::getEventnameFromUrl() === 'BuildAutoloader')
		) {
			self::init();
		}

		if (isset(self::$classMap[$name])) {
			return true;
		} else {
			return false;
		}
	}

	// Initialize the autoloader
	public static function init(): void
	{
		self::$excludedPath = [
			DEPLOY_ROOT . 'vendor',
			DEPLOY_ROOT . 'tools',
			DEPLOY_ROOT . 'public',
			DEPLOY_ROOT . 'rector.php',
			DEPLOY_ROOT . 'tmp',
		];

		if (!self::$initialized_failsafe) {
			// Get all PHP files and build the class map
			$projectPhpFiles = self::getProjectPhpFiles(self::$excludedPath);

			foreach ($projectPhpFiles as $file) {
				$className = self::getClassNameFromFile($file);
				$real_path = $file->getRealPath();

				if ($className !== null && $real_path !== false && $real_path !== '') {
					$priority = self::getClassPathPriority($real_path);

					if (
						!isset(self::$classMap[$className])
						|| !isset(self::$classPriorityMap[$className])
						|| $priority >= self::$classPriorityMap[$className]
					) {
						self::$classMap[$className] = $real_path;
						self::$classPriorityMap[$className] = $priority;
					}
				}
			}
			self::$initialized_failsafe = true;
		}

		ksort(self::$classMap);
	}

	/** @return array<class-string, string> */
	public static function getAutoloadMap(): array
	{
		self::init();

		return self::$classMap;
	}

	public static function register_failsafe_autoloader(): void
	{
		spl_autoload_register(function ($class) {
			if (!self::$fallback_header_sent && PHP_SAPI !== 'cli' && !headers_sent()) {
				header('X-Radaptor-Autoloader-Fallback: 1');
				header('X-Radaptor-Autoloader-Fallback-Class: ' . $class);
				self::$fallback_header_sent = true;
			}

			self::init();

			if (array_key_exists($class, AutoloaderFailsafe::$classMap)) {
				$absolute_path = AutoloaderFailsafe::$classMap[$class];
				require_once $absolute_path;
			}
		});
	}

	public static function reset(): void
	{
		self::$classMap = [];
		self::$classPriorityMap = [];
		self::$initialized_failsafe = false;
		self::$fallback_header_sent = false;
	}

	private static function getClassPathPriority(string $absolutePath): int
	{
		return PackagePathHelper::getPathPriority($absolutePath);
	}
}
