<?php

define('RADAPTOR_CLI', getcwd() . '/');
define('USE_PERSISTENT_CACHE', false);

// Set CLI-specific application identifier for audit logging
putenv('APP_APPLICATION_IDENTIFIER=Radaptor CLI');

// Include necessary files and bootstrap
require_once 'radaptor/radaptor-framework/bootstrap.php';

$session_handler = new CLISessionHandler();
session_set_save_handler($session_handler, true);
session_start();

// Initialize the kernel
Kernel::initialize();

// Dispatch the CLI command
CLICommandResolver::dispatch();

echo "\n\0";
