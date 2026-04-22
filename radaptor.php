<?php

define('RADAPTOR_CLI', __DIR__ . '/');
define('USE_PERSISTENT_CACHE', false);

// Set CLI-specific application identifier for audit logging
putenv('APP_APPLICATION_IDENTIFIER=Radaptor CLI');

// Ensure the framework package exists before delegating into its CLI runtime.
require_once __DIR__ . '/bootstrap/bootstrap.package_locator.php';
radaptorAppBootstrapEnsureCliFrameworkAvailable(__DIR__);
require_once __DIR__ . '/bootstrap/bootstrap.php';

$session_handler = new CLISessionHandler();
session_set_save_handler($session_handler, true);
session_start();

// Initialize the kernel
Kernel::initialize();

// Fresh installs bootstrap the framework before the full package set exists.
// Clear discovery/config caches before dispatch so install/update rebuilds see newly extracted packages.
if (class_exists('PackageConfig')) {
	PackageConfig::reset();
}

if (class_exists('PackagePathHelper')) {
	PackagePathHelper::reset();
}

if (class_exists('PackageThemeScanHelper')) {
	PackageThemeScanHelper::reset();
}

// Dispatch the CLI command
CLICommandResolver::dispatch();

echo "\n\0";
