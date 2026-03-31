<?php

require_once dirname(__DIR__, 2) . '/radaptor/radaptor-framework/bootstrap.php';

//include DEPLOY_ROOT . "/radaptor/radaptor-framework/classes/class.Db.php";
//include DEPLOY_ROOT . "/radaptor/radaptor-framework/classes/class.DbHelper.php";
//include DEPLOY_ROOT . "/radaptor/radaptor-framework/classes/class.SessionHandlerMysql.php";

//include DEPLOY_ROOT . "/radaptor/radaptor-framework/modules/PersistentCache/classes/class.PersistentCacheMysql.php";

//$sessionHandler = new SessionHandlerMysql();
//session_set_save_handler($sessionHandler, true);

// moved session start to Request::start_session()
//session_start();

// If the persistent cache is enabled, then trying to get the page from the persistent cache, which will exit on success
if (Config::APP_PERSISTENT_CACHE_ENABLED->value()) {
	$framework_root = PackagePathHelper::getFrameworkRoot() ?? (DEPLOY_ROOT . 'radaptor/radaptor-framework');
	include rtrim($framework_root, '/') . "/modules/PersistentCache/persistent_cache_reader.php";
}

//include "redirector.php";

Kernel::initialize();

EventResolver::dispatch();
