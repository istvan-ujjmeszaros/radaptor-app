<?php

if (!function_exists('radaptorAppBootstrapNormalizePath')) {
	function radaptorAppBootstrapNormalizePath(string $path): string
	{
		$path = str_replace('\\', '/', $path);
		$real = realpath($path);

		if ($real !== false) {
			return rtrim(str_replace('\\', '/', $real), '/');
		}

		return rtrim($path, '/');
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
			'packages/dev/core/framework',
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

if (!function_exists('radaptorAppBootstrapRequireFrameworkBootstrap')) {
	function radaptorAppBootstrapRequireFrameworkBootstrap(string $bootstrap_filename, string $bootstrap_dir): void
	{
		$app_root = rtrim(radaptorAppBootstrapNormalizePath(dirname($bootstrap_dir)), '/') . '/';
		putenv('RADAPTOR_APP_ROOT=' . $app_root);
		$framework_root = radaptorAppBootstrapResolveFrameworkRoot($app_root);

		if (!is_string($framework_root) || !is_dir($framework_root)) {
			throw new RuntimeException(
				"Framework package is not available under '{$app_root}'. Run the app bootstrap/init flow first."
			);
		}

		$bootstrap_path = rtrim($framework_root, '/') . '/' . ltrim($bootstrap_filename, '/');

		if (!is_file($bootstrap_path)) {
			throw new RuntimeException("Framework bootstrap file is missing: {$bootstrap_path}");
		}

		require_once $bootstrap_path;
	}
}
