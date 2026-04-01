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

// Dispatch the CLI command
CLICommandResolver::dispatch();

echo "\n\0";
