<?php

if (!function_exists('radaptorAppBootstrapNormalizePath')) {
	function radaptorAppBootstrapNormalizePath(string $path): string
	{
		$path = str_replace('\\', '/', $path);

		// Do NOT use realpath() — it follows symlinks, which would corrupt the
		// logical registry path when a local override remaps it onto an editable
		// checkout under RADAPTOR_DEV_ROOT.
		if ($path !== '' && $path[0] === '/') {
			$prefix = '/';
			$path = substr($path, 1);
		} else {
			$prefix = '';
		}

		$parts = [];

		foreach (explode('/', $path) as $segment) {
			if ($segment === '' || $segment === '.') {
				continue;
			}

			if ($segment === '..' && $parts !== [] && end($parts) !== '..') {
				array_pop($parts);
			} else {
				$parts[] = $segment;
			}
		}

		return rtrim($prefix . implode('/', $parts), '/');
	}
}

if (!function_exists('radaptorAppBootstrapResolveStoredPath')) {
	function radaptorAppBootstrapResolveStoredPath(string $app_root, string $path): string
	{
		if (str_starts_with($path, '/')) {
			return radaptorAppBootstrapNormalizePath($path);
		}

		return radaptorAppBootstrapNormalizePath(rtrim($app_root, '/') . '/' . ltrim($path, '/'));
	}
}

if (!function_exists('radaptorAppBootstrapDecodeJsonFile')) {
	function radaptorAppBootstrapDecodeJsonFile(string $path): ?array
	{
		if (!is_file($path)) {
			return null;
		}

		$json = file_get_contents($path);

		if ($json === false || trim($json) === '') {
			return null;
		}

		try {
			$data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
		} catch (JsonException) {
			return null;
		}

		return is_array($data) ? $data : null;
	}
}

if (!function_exists('radaptorAppBootstrapLocalOverridesDisabled')) {
	function radaptorAppBootstrapLocalOverridesDisabled(): bool
	{
		global $argv;

		foreach ($argv ?? [] as $arg) {
			if ($arg === '--ignore-local-overrides') {
				return true;
			}
		}

		$value = strtolower(trim((string) getenv('RADAPTOR_DISABLE_LOCAL_OVERRIDES')));

		return in_array($value, ['1', 'true', 'yes', 'on'], true);
	}
}

if (!function_exists('radaptorAppBootstrapGetDevRoot')) {
	function radaptorAppBootstrapGetDevRoot(string $app_root): string
	{
		$workspace_mode = trim((string) getenv('RADAPTOR_WORKSPACE_DEV_MODE'));

		if ($workspace_mode !== '1') {
			throw new RuntimeException(
				'RADAPTOR_WORKSPACE_DEV_MODE=1 must be set when local package overrides are active. '
				. 'Enable the workspace package-dev compose override or pass --ignore-local-overrides.'
			);
		}

		$configured = trim((string) getenv('RADAPTOR_DEV_ROOT'));

		if ($configured !== '') {
			return radaptorAppBootstrapNormalizePath($configured);
		}

		throw new RuntimeException(
			'RADAPTOR_WORKSPACE_DEV_MODE=1 and RADAPTOR_DEV_ROOT must be set when local package overrides are active. '
			. 'Enable the workspace package-dev compose override or pass --ignore-local-overrides.'
		);
	}
}

if (!function_exists('radaptorAppBootstrapResolveLocalOverrideLocation')) {
	function radaptorAppBootstrapResolveLocalOverrideLocation(string $location, string $app_root): string
	{
		$location = trim(str_replace('\\', '/', $location));

		if ($location === '') {
			throw new RuntimeException('Local package override source.location must not be empty.');
		}

		if (str_starts_with($location, '/')) {
			throw new RuntimeException("Local package override location '{$location}' must be relative.");
		}

		$segments = explode('/', $location);

		foreach ($segments as $segment) {
			if ($segment === '' || $segment === '.' || $segment === '..') {
				throw new RuntimeException(
					"Local package override location '{$location}' contains an invalid path segment."
				);
			}

			if (preg_match('/^[A-Za-z0-9._-]+$/', $segment) !== 1) {
				throw new RuntimeException(
					"Local package override location '{$location}' contains unsupported characters."
				);
			}
		}

		$dev_root = radaptorAppBootstrapGetDevRoot($app_root);
		$resolved = radaptorAppBootstrapNormalizePath(rtrim($dev_root, '/') . '/' . implode('/', $segments));

		if (!str_starts_with($resolved . '/', rtrim($dev_root, '/') . '/')) {
			throw new RuntimeException("Local package override location '{$location}' resolves outside RADAPTOR_DEV_ROOT.");
		}

		$normalized_app_root = rtrim(radaptorAppBootstrapNormalizePath($app_root), '/');
		$app_local_dev_root = $normalized_app_root . '/packages-dev';

		if (
			($resolved === $normalized_app_root || str_starts_with($resolved, $normalized_app_root . '/'))
			&& !str_starts_with($resolved . '/', $app_local_dev_root . '/')
		) {
			throw new RuntimeException(
				"Local package override location '{$location}' must resolve under RADAPTOR_DEV_ROOT, "
				. "and app-local overrides may only use packages-dev."
			);
		}

		return $resolved;
	}
}

if (!function_exists('radaptorAppBootstrapDecodeJsonFileOrThrow')) {
	function radaptorAppBootstrapDecodeJsonFileOrThrow(string $path, string $label): array
	{
		$json = file_get_contents($path);

		if ($json === false) {
			throw new RuntimeException("Unable to read {$label}: {$path}");
		}

		try {
			$data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
		} catch (JsonException $exception) {
			throw new RuntimeException("Invalid JSON in {$label} {$path}: {$exception->getMessage()}", 0, $exception);
		}

		if (!is_array($data)) {
			throw new RuntimeException(ucfirst($label) . " must decode to an object: {$path}");
		}

		return $data;
	}
}

if (!function_exists('radaptorAppBootstrapLoadLocalOverrideDocument')) {
	function radaptorAppBootstrapLoadLocalOverrideDocument(string $app_root): ?array
	{
		if (radaptorAppBootstrapLocalOverridesDisabled()) {
			return null;
		}

		$path = rtrim($app_root, '/') . '/radaptor.local.json';

		if (!is_file($path)) {
			return null;
		}

		$data = radaptorAppBootstrapDecodeJsonFileOrThrow($path, 'local package override');
		$allowed_root_keys = ['manifest_version', 'core', 'themes'];

		foreach (array_keys($data) as $key) {
			if (!in_array($key, $allowed_root_keys, true)) {
				throw new RuntimeException(
					"Local package override file may only define 'manifest_version', 'core', and 'themes'. Invalid key: '{$key}'."
				);
			}
		}

		foreach (['core', 'themes'] as $section) {
			$packages = $data[$section] ?? null;

			if ($packages === null) {
				continue;
			}

			if (!is_array($packages)) {
				throw new RuntimeException("Local package override section '{$section}' must be an object.");
			}

			foreach ($packages as $id => $package) {
				if (!is_array($package)) {
					throw new RuntimeException("Local package override entry '{$section}.{$id}' must be an object.");
				}

				if (array_keys($package) !== ['source']) {
					throw new RuntimeException("Local package override entry '{$section}.{$id}' may only define 'source'.");
				}

				$source = $package['source'] ?? null;

				if (!is_array($source)) {
					throw new RuntimeException("Local package override entry '{$section}.{$id}.source' must be an object.");
				}

				foreach (array_keys($source) as $key) {
					if (!in_array($key, ['type', 'location'], true)) {
						throw new RuntimeException(
							"Local package override '{$section}.{$id}.source' uses unsupported key '{$key}'."
						);
					}
				}

				if (($source['type'] ?? null) !== 'dev') {
					throw new RuntimeException("Local package override '{$section}.{$id}' must use source.type='dev'.");
				}

				radaptorAppBootstrapResolveLocalOverrideLocation((string) ($source['location'] ?? ''), $app_root);
			}
		}

		return $data;
	}
}

if (!function_exists('radaptorAppBootstrapResolveFrameworkRootFromLocalOverride')) {
	function radaptorAppBootstrapResolveFrameworkRootFromLocalOverride(array $data, string $app_root): ?string
	{
		$framework = $data['core']['framework'] ?? null;

		if (!is_array($framework)) {
			return null;
		}

		$source = $framework['source'] ?? null;

		if (!is_array($source)) {
			throw new RuntimeException("Local package override entry 'core.framework.source' must be an object.");
		}

		$root = radaptorAppBootstrapResolveLocalOverrideLocation((string) ($source['location'] ?? ''), $app_root);

		if (!is_dir($root)) {
			throw new RuntimeException("Local framework override directory does not exist: {$root}");
		}

		return $root;
	}
}

if (!function_exists('radaptorAppBootstrapResolveFrameworkRootFromDocument')) {
	function radaptorAppBootstrapResolveFrameworkRootFromDocument(array $data, string $app_root): ?string
	{
		$framework = $data['core']['framework'] ?? null;

		if (!is_array($framework)) {
			return null;
		}

		foreach (['resolved', 'source'] as $section) {
			$source = $framework[$section] ?? null;

			if (!is_array($source)) {
				continue;
			}

			$path = trim((string) ($source['path'] ?? ''));

			if ($path === '') {
				continue;
			}

			$root = radaptorAppBootstrapResolveStoredPath($app_root, $path);

			if (is_dir($root)) {
				return $root;
			}
		}

		return null;
	}
}

if (!function_exists('radaptorAppBootstrapResolveFrameworkRoot')) {
	function radaptorAppBootstrapResolveFrameworkRoot(string $app_root): ?string
	{
		$app_root = rtrim(radaptorAppBootstrapNormalizePath($app_root), '/') . '/';

		$local_override = radaptorAppBootstrapLoadLocalOverrideDocument($app_root);

		if (is_array($local_override)) {
			$local_lock_path = $app_root . 'radaptor.local.lock.json';

			if (is_file($local_lock_path)) {
				$local_lock = radaptorAppBootstrapDecodeJsonFileOrThrow($local_lock_path, 'local package lockfile');
				$local_root = radaptorAppBootstrapResolveFrameworkRootFromDocument($local_lock, $app_root);

				if (!is_string($local_root)) {
					throw new RuntimeException(
						'Active radaptor.local.lock.json does not resolve an existing core.framework package root.'
					);
				}

				return $local_root;
			}

			$local_manifest_root = radaptorAppBootstrapResolveFrameworkRootFromLocalOverride($local_override, $app_root);

			if (is_string($local_manifest_root)) {
				return $local_manifest_root;
			}
		}

		foreach (['radaptor.lock.json', 'radaptor.json'] as $document_name) {
			$data = radaptorAppBootstrapDecodeJsonFile($app_root . $document_name);

			if (!is_array($data)) {
				continue;
			}

			$resolved_root = radaptorAppBootstrapResolveFrameworkRootFromDocument($data, $app_root);

			if (is_string($resolved_root)) {
				return $resolved_root;
			}
		}

		foreach ([
			'packages/registry/core/framework',
		] as $relative_path) {
			$candidate = radaptorAppBootstrapResolveStoredPath($app_root, $relative_path);

			if (is_dir($candidate)) {
				return $candidate;
			}
		}

		return null;
	}
}

if (!function_exists('radaptorAppBootstrapGetPlaceholderRegistryUrl')) {
	function radaptorAppBootstrapGetPlaceholderRegistryUrl(): string
	{
		return 'https://packages.example.invalid/registry.json';
	}
}

if (!function_exists('radaptorAppBootstrapNormalizeRegistryUrl')) {
	function radaptorAppBootstrapNormalizeRegistryUrl(string $url): ?string
	{
		$url = trim($url);

		if ($url === '') {
			return null;
		}

		$parts = parse_url($url);

		if (!is_array($parts)) {
			return null;
		}

		$scheme = strtolower((string) ($parts['scheme'] ?? ''));

		if (!in_array($scheme, ['http', 'https', 'file'], true)) {
			return null;
		}

		return $url;
	}
}

if (!function_exists('radaptorAppBootstrapBuildUrlAuthority')) {
	function radaptorAppBootstrapBuildUrlAuthority(array $parts): string
	{
		$scheme = strtolower((string) ($parts['scheme'] ?? ''));

		if ($scheme === '') {
			throw new RuntimeException('Cannot build URL authority without a scheme.');
		}

		$authority = $scheme . '://';

		if (isset($parts['user'])) {
			$authority .= (string) $parts['user'];

			if (isset($parts['pass'])) {
				$authority .= ':' . (string) $parts['pass'];
			}

			$authority .= '@';
		}

		$host = (string) ($parts['host'] ?? '');

		if ($host !== '' && str_contains($host, ':') && !str_starts_with($host, '[')) {
			$host = '[' . $host . ']';
		}

		$authority .= $host;

		if (isset($parts['port'])) {
			$authority .= ':' . $parts['port'];
		}

		return $authority;
	}
}

if (!function_exists('radaptorAppBootstrapBuildUrlPathSuffix')) {
	function radaptorAppBootstrapBuildUrlPathSuffix(array $parts): string
	{
		$suffix = (string) ($parts['path'] ?? '/');

		if (isset($parts['query']) && $parts['query'] !== '') {
			$suffix .= '?' . $parts['query'];
		}

		if (isset($parts['fragment']) && $parts['fragment'] !== '') {
			$suffix .= '#' . $parts['fragment'];
		}

		return $suffix;
	}
}

if (!function_exists('radaptorAppBootstrapHasSameUrlAuthority')) {
	function radaptorAppBootstrapHasSameUrlAuthority(array $left, array $right): bool
	{
		return strtolower((string) ($left['scheme'] ?? '')) === strtolower((string) ($right['scheme'] ?? ''))
			&& ((string) ($left['host'] ?? '')) === ((string) ($right['host'] ?? ''))
			&& ((int) ($left['port'] ?? 0)) === ((int) ($right['port'] ?? 0));
	}
}

if (!function_exists('radaptorAppBootstrapResolveRegistryUrl')) {
	function radaptorAppBootstrapResolveRegistryUrl(string $app_root): ?string
	{
		$env_registry = getenv('RADAPTOR_REGISTRY_URL');

		if (is_string($env_registry)) {
			$resolved_env_registry = radaptorAppBootstrapNormalizeRegistryUrl($env_registry);

			if (is_string($resolved_env_registry)) {
				return $resolved_env_registry;
			}
		}

		$manifest = radaptorAppBootstrapDecodeJsonFile(rtrim($app_root, '/') . '/radaptor.json');
		$manifest_registry = $manifest['registries']['default']['url'] ?? null;

		if (!is_string($manifest_registry)) {
			return null;
		}

		$resolved_manifest_registry = radaptorAppBootstrapNormalizeRegistryUrl($manifest_registry);

		if ($resolved_manifest_registry === null) {
			return null;
		}

		if ($resolved_manifest_registry === radaptorAppBootstrapGetPlaceholderRegistryUrl()) {
			return null;
		}

		return $resolved_manifest_registry;
	}
}

if (!function_exists('radaptorAppBootstrapResolveUrl')) {
	function radaptorAppBootstrapResolveUrl(string $base_url, string $candidate): string
	{
		$base = parse_url($base_url);

		if (!is_array($base) || !isset($base['scheme'])) {
			throw new RuntimeException("Unable to resolve registry URL base: {$base_url}");
		}

		$normalized_candidate = radaptorAppBootstrapNormalizeRegistryUrl($candidate);

		if ($normalized_candidate !== null) {
			$candidate_parts = parse_url($normalized_candidate);

			if (!is_array($candidate_parts)) {
				throw new RuntimeException("Unable to parse resolved URL candidate: {$candidate}");
			}

			$placeholder_parts = parse_url(radaptorAppBootstrapGetPlaceholderRegistryUrl());

			if (
				is_array($placeholder_parts)
				&& radaptorAppBootstrapHasSameUrlAuthority($candidate_parts, $placeholder_parts)
				&& !radaptorAppBootstrapHasSameUrlAuthority($base, $placeholder_parts)
				&& in_array(strtolower((string) ($candidate_parts['scheme'] ?? '')), ['http', 'https'], true)
			) {
				return radaptorAppBootstrapBuildUrlAuthority($base) . radaptorAppBootstrapBuildUrlPathSuffix($candidate_parts);
			}

			return $normalized_candidate;
		}

		if ($base['scheme'] === 'file') {
			$base_path = $base['path'] ?? '';
			$base_dir = rtrim(str_replace('\\', '/', dirname($base_path)), '/');
			$path = str_starts_with($candidate, '/')
				? $candidate
				: ($base_dir . '/' . ltrim($candidate, '/'));
			$authority = radaptorAppBootstrapBuildUrlAuthority($base);

			return $authority . radaptorAppBootstrapNormalizeRelativeUrlPath($path);
		}

		$authority = radaptorAppBootstrapBuildUrlAuthority($base);

		if (str_starts_with($candidate, '/')) {
			return $authority . $candidate;
		}

		$base_path = $base['path'] ?? '/';
		$base_dir = rtrim(str_replace('\\', '/', dirname($base_path)), '/');
		$joined_path = radaptorAppBootstrapNormalizeRelativeUrlPath($base_dir . '/' . $candidate);

		return $authority . $joined_path;
	}
}

if (!function_exists('radaptorAppBootstrapNormalizeRelativeUrlPath')) {
	function radaptorAppBootstrapNormalizeRelativeUrlPath(string $path): string
	{
		$path = str_replace('\\', '/', $path);
		$prefix = str_starts_with($path, '/') ? '/' : '';
		$segments = [];

		foreach (explode('/', $path) as $segment) {
			if ($segment === '' || $segment === '.') {
				continue;
			}

			if ($segment === '..') {
				array_pop($segments);

				continue;
			}

			$segments[] = $segment;
		}

		return $prefix . implode('/', $segments);
	}
}

if (!function_exists('radaptorAppBootstrapFetchJsonUrl')) {
	function radaptorAppBootstrapFetchJsonUrl(string $url): array
	{
		$context = stream_context_create([
			'http' => [
				'timeout' => 30,
				'follow_location' => 1,
				'user_agent' => 'RadaptorAppBootstrap/1.0',
			],
		]);

		$json = @file_get_contents($url, false, $context);

		if ($json === false) {
			$error = error_get_last();
			$suffix = is_array($error) ? ': ' . $error['message'] : '';

			throw new RuntimeException("Unable to fetch package registry URL: {$url}{$suffix}");
		}

		try {
			$data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
		} catch (JsonException $e) {
			throw new RuntimeException("Invalid package registry JSON at {$url}: " . $e->getMessage(), 0, $e);
		}

		if (!is_array($data)) {
			throw new RuntimeException("Package registry JSON root must be an object: {$url}");
		}

		return $data;
	}
}

if (!function_exists('radaptorAppBootstrapResolveFrameworkBootstrapRequest')) {
	function radaptorAppBootstrapResolveFrameworkBootstrapRequest(string $app_root, string $registry_url): array
	{
		$lock = radaptorAppBootstrapDecodeJsonFile(rtrim($app_root, '/') . '/radaptor.lock.json');
		$framework = $lock['core']['framework'] ?? null;

		if (!is_array($framework)) {
			throw new RuntimeException(
				'Framework bootstrap requires a committed radaptor.lock.json with a locked core.framework package.'
			);
		}

		$package_name = trim((string) ($framework['package'] ?? ''));
		$resolved_version = trim((string) ($framework['resolved']['version'] ?? ''));
		$resolved = $framework['resolved'] ?? null;

		if ($package_name === '' || $resolved_version === '' || !is_array($resolved)) {
			throw new RuntimeException(
				'Framework bootstrap requires a locked package name and version in radaptor.lock.json.'
			);
		}

		$dist_url = trim((string) ($resolved['dist_url'] ?? ''));
		$dist_sha256 = strtolower(trim((string) ($resolved['dist_sha256'] ?? '')));

		if ($dist_url === '' || $dist_sha256 === '') {
			throw new RuntimeException(
				"Locked framework package {$package_name} version {$resolved_version} is missing dist metadata."
			);
		}

		return [
			'package' => $package_name,
			'version' => $resolved_version,
			'dist_url' => radaptorAppBootstrapResolveUrl($registry_url, $dist_url),
			'dist_sha256' => $dist_sha256,
			'target_dir' => radaptorAppBootstrapResolveStoredPath($app_root, 'packages/registry/core/framework'),
		];
	}
}

if (!function_exists('radaptorAppBootstrapDownloadFile')) {
	function radaptorAppBootstrapDownloadFile(string $url, string $target_path): string
	{
		$context = stream_context_create([
			'http' => [
				'timeout' => 60,
				'follow_location' => 1,
				'user_agent' => 'RadaptorAppBootstrap/1.0',
			],
		]);

		$read_handle = @fopen($url, 'rb', false, $context);

		if ($read_handle === false) {
			$error = error_get_last();
			$suffix = is_array($error) ? ': ' . $error['message'] : '';

			throw new RuntimeException("Unable to download framework bootstrap archive from {$url}{$suffix}");
		}

		$write_handle = fopen($target_path, 'wb');

		if ($write_handle === false) {
			fclose($read_handle);

			throw new RuntimeException("Unable to open temporary archive path: {$target_path}");
		}

		$hash = hash_init('sha256');

		try {
			while (!feof($read_handle)) {
				$chunk = fread($read_handle, 1024 * 1024);

				if ($chunk === false) {
					throw new RuntimeException("Failed reading framework bootstrap archive from {$url}");
				}

				if ($chunk === '') {
					continue;
				}

				hash_update($hash, $chunk);

				if (fwrite($write_handle, $chunk) === false) {
					throw new RuntimeException("Failed writing temporary framework archive to {$target_path}");
				}
			}
		} finally {
			fclose($read_handle);
			fclose($write_handle);
		}

		return hash_final($hash);
	}
}

if (!function_exists('radaptorAppBootstrapDeleteDirectory')) {
	function radaptorAppBootstrapRunFilesystemOperation(callable $operation, string $error_message): mixed
	{
		$warning = null;
		set_error_handler(static function (int $_severity, string $message) use (&$warning): bool {
			$warning = $message;

			return true;
		});

		try {
			$result = $operation();
		} finally {
			restore_error_handler();
		}

		if ($result === false) {
			throw new RuntimeException($error_message . ($warning !== null ? ': ' . $warning : ''));
		}

		return $result;
	}
}

if (!function_exists('radaptorAppBootstrapEnsureDirectory')) {
	function radaptorAppBootstrapEnsureDirectory(string $directory): void
	{
		if (is_dir($directory)) {
			return;
		}

		$warning = null;
		set_error_handler(static function (int $_severity, string $message) use (&$warning): bool {
			$warning = $message;

			return true;
		});

		try {
			$created = mkdir($directory, 0o777, true);
		} finally {
			restore_error_handler();
		}

		clearstatcache(true, $directory);

		if ($created || is_dir($directory)) {
			return;
		}

		throw new RuntimeException(
			"Unable to create bootstrap directory: {$directory}" . ($warning !== null ? ': ' . $warning : '')
		);
	}
}

if (!function_exists('radaptorAppBootstrapDeleteDirectory')) {
	function radaptorAppBootstrapDeleteDirectory(string $directory): void
	{
		if (!is_dir($directory)) {
			return;
		}

		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
			RecursiveIteratorIterator::CHILD_FIRST
		);

		foreach ($iterator as $item) {
			if ($item->isDir()) {
				radaptorAppBootstrapRunFilesystemOperation(
					static fn (): bool => rmdir($item->getPathname()),
					"Unable to remove bootstrap directory: {$item->getPathname()}"
				);
			} else {
				radaptorAppBootstrapRunFilesystemOperation(
					static fn (): bool => unlink($item->getPathname()),
					"Unable to remove bootstrap file: {$item->getPathname()}"
				);
			}
		}

		radaptorAppBootstrapRunFilesystemOperation(
			static fn (): bool => rmdir($directory),
			"Unable to remove bootstrap directory: {$directory}"
		);
	}
}

if (!function_exists('radaptorAppBootstrapCopyDirectory')) {
	function radaptorAppBootstrapCopyDirectory(string $source_directory, string $target_directory): void
	{
		if (!is_dir($source_directory)) {
			throw new RuntimeException("Bootstrap source directory is missing: {$source_directory}");
		}

		radaptorAppBootstrapEnsureDirectory($target_directory);

		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($source_directory, FilesystemIterator::SKIP_DOTS),
			RecursiveIteratorIterator::SELF_FIRST
		);

		foreach ($iterator as $item) {
			$relative_path = substr($item->getPathname(), strlen(rtrim($source_directory, '/')) + 1);
			$target_path = rtrim($target_directory, '/') . '/' . $relative_path;

			if ($item->isDir()) {
				radaptorAppBootstrapEnsureDirectory($target_path);

				continue;
			}

			radaptorAppBootstrapEnsureDirectory(dirname($target_path));
			radaptorAppBootstrapRunFilesystemOperation(
				static fn (): bool => copy($item->getPathname(), $target_path),
				"Unable to copy bootstrap file into {$target_path}"
			);
		}
	}
}

if (!function_exists('radaptorAppBootstrapInstallFrameworkPackage')) {
	function radaptorAppBootstrapInstallFrameworkPackage(string $app_root, string $registry_url): string
	{
		$request = radaptorAppBootstrapResolveFrameworkBootstrapRequest($app_root, $registry_url);

		if (!class_exists(ZipArchive::class)) {
			throw new RuntimeException('ZipArchive extension is required for framework bootstrap installs.');
		}

		$temp_archive = tempnam(sys_get_temp_dir(), 'radaptor-framework-');
		$temp_extract_root = sys_get_temp_dir() . '/radaptor-framework-extract-' . bin2hex(random_bytes(8));

		if ($temp_archive === false) {
			throw new RuntimeException('Unable to allocate temporary archive path for framework bootstrap.');
		}

		radaptorAppBootstrapEnsureDirectory($temp_extract_root);

		try {
			$actual_hash = radaptorAppBootstrapDownloadFile($request['dist_url'], $temp_archive);

			if (!hash_equals($request['dist_sha256'], strtolower($actual_hash))) {
				throw new RuntimeException(
					"Framework bootstrap archive hash mismatch: expected {$request['dist_sha256']}, got {$actual_hash}"
				);
			}

			$zip = new ZipArchive();
			$open_result = $zip->open($temp_archive);

			if ($open_result !== true) {
				throw new RuntimeException("Unable to open framework bootstrap archive: {$request['dist_url']}");
			}

			try {
				if (!$zip->extractTo($temp_extract_root)) {
					throw new RuntimeException("Unable to extract framework bootstrap archive: {$request['dist_url']}");
				}
			} finally {
				$zip->close();
			}

			$entries = array_values(array_filter(scandir($temp_extract_root) ?: [], static function (string $entry): bool {
				return $entry !== '.' && $entry !== '..' && $entry !== '__MACOSX';
			}));

			if ($entries === []) {
				throw new RuntimeException('Extracted framework bootstrap archive is empty.');
			}

			$package_root = $temp_extract_root;

			if (count($entries) === 1 && is_dir($temp_extract_root . '/' . $entries[0])) {
				$package_root = $temp_extract_root . '/' . $entries[0];
			} elseif (!is_file($temp_extract_root . '/.registry-package.json') && !is_file($temp_extract_root . '/bootstrap.php')) {
				throw new RuntimeException('Unable to determine extracted framework package root.');
			}

			$target_dir = $request['target_dir'];

			$app_root_prefix = rtrim(radaptorAppBootstrapNormalizePath($app_root), '/') . '/';
			$normalized_target = radaptorAppBootstrapNormalizePath($target_dir);

			if (!str_starts_with($normalized_target, $app_root_prefix)) {
				throw new RuntimeException(
					"Bootstrap install refused: target '{$normalized_target}' is outside app root."
				);
			}

			$relative = substr($normalized_target, strlen($app_root_prefix));

			if (!str_starts_with($relative, 'packages/registry/')) {
				throw new RuntimeException(
					"Bootstrap install refused: target '{$relative}' is not under packages/registry/."
				);
			}

			$check_path = $normalized_target;
			$stop_path = rtrim($app_root_prefix, '/');

			while ($check_path !== $stop_path && $check_path !== '' && $check_path !== '/') {
				if (is_link($check_path)) {
					throw new RuntimeException(
						"Bootstrap install refused: path component '{$check_path}' is a symlink."
					);
				}

				$check_path = dirname($check_path);
			}

			radaptorAppBootstrapDeleteDirectory($target_dir);

			$target_parent = dirname($target_dir);
			radaptorAppBootstrapEnsureDirectory($target_parent);

			radaptorAppBootstrapCopyDirectory($package_root, $target_dir);

			return $target_dir;
		} finally {
			if (is_file($temp_archive)) {
				unlink($temp_archive);
			}

			radaptorAppBootstrapDeleteDirectory($temp_extract_root);
		}
	}
}

if (!function_exists('radaptorAppBootstrapEnsureCliFrameworkAvailable')) {
	function radaptorAppBootstrapEnsureCliFrameworkAvailable(string $app_root): void
	{
		$app_root = rtrim(radaptorAppBootstrapNormalizePath($app_root), '/') . '/';

		$framework_root = radaptorAppBootstrapResolveFrameworkRoot($app_root);

		if (is_string($framework_root) && is_file(rtrim($framework_root, '/') . '/bootstrap.php')) {
			return;
		}

		$registry_url = radaptorAppBootstrapResolveRegistryUrl($app_root);

		if (!is_string($registry_url)) {
			throw new RuntimeException(
				'Framework package is not available and no registry URL is configured. '
				. 'Set RADAPTOR_REGISTRY_URL or commit a real default registry URL before running `php radaptor.php install --json`.'
			);
		}

		radaptorAppBootstrapInstallFrameworkPackage($app_root, $registry_url);
	}
}

if (!function_exists('radaptorAppBootstrapRequireFrameworkBootstrap')) {
	function radaptorAppBootstrapRequireFrameworkBootstrap(string $bootstrap_filename, string $bootstrap_dir): void
	{
		$app_root = rtrim(radaptorAppBootstrapNormalizePath(dirname($bootstrap_dir)), '/') . '/';
		putenv('RADAPTOR_APP_ROOT=' . $app_root);
		$framework_root = radaptorAppBootstrapResolveFrameworkRoot($app_root);

		if (!is_string($framework_root) || !is_dir($framework_root)) {
			throw new RuntimeException(
				"Framework package is not available under '{$app_root}'. Run `php radaptor.php install --json` first."
			);
		}

		$bootstrap_path = rtrim($framework_root, '/') . '/' . ltrim($bootstrap_filename, '/');

		if (!is_file($bootstrap_path)) {
			throw new RuntimeException("Framework bootstrap file is missing: {$bootstrap_path}");
		}

		require_once $bootstrap_path;
	}
}
