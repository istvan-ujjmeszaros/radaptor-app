<?php

require_once dirname(__DIR__, 2) . '/bootstrap/bootstrap.php';

// If the persistent cache is enabled, then trying to get the page from the persistent cache, which will exit on success
if (Config::APP_PERSISTENT_CACHE_ENABLED->value()) {
	$framework_root = PackagePathHelper::getFrameworkRoot();

	if (!is_string($framework_root) || !is_dir($framework_root)) {
		throw new RuntimeException('Framework package root is unavailable.');
	}

	include rtrim($framework_root, '/') . "/modules/PersistentCache/persistent_cache_reader.php";
}

Kernel::initialize();

EventResolver::dispatch();
