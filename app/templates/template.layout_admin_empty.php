<?php assert(isset($this) && $this instanceof Template); ?>
<?php if (class_exists('LibrariesRadaptorPortalAdmin')): ?>
	<?php $this->registerLibrary('JQUERY'); ?>
	<?php $this->registerLibrary('__RADAPTOR_PORTAL_ADMIN_SITE'); ?>
<?php endif; ?>
<?php
$lang = (string)($this->props['lang'] ?? substr(Kernel::getLocale(), 0, 2));
$site_name = (string)($this->props['site_name'] ?? Config::APP_SITE_NAME->value());
$document_title = (string)($this->props['document_title'] ?? $site_name);
?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>">
<head>
	<meta charset="utf-8">
	<title><?= e($document_title) ?></title>
	<meta name="robots" content="NOINDEX, NOFOLLOW">
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="shortcut icon" href="/favicon.ico">
	<link rel="icon" href="/favicon.ico" type="image/x-icon">
	<?= $this->getRenderer()->getCss(); ?>
	<?= $this->getRenderer()->getJsTop(); ?>
	<style>
		.login-shell {
			min-height: 100vh;
			display: flex;
			align-items: center;
			justify-content: center;
			padding: 2rem 1rem;
			background: linear-gradient(180deg, #f2f5f9 0%, #e6edf5 100%);
		}
		.login-card {
			width: min(100%, 28rem);
			padding: 2rem;
			background: #fff;
			border-radius: 1rem;
			box-shadow: 0 1.25rem 3rem rgba(34, 56, 78, 0.12);
		}
		.login-card h1 {
			margin: 0 0 0.5rem;
			font-size: 1.5rem;
		}
		.login-card p {
			margin: 0 0 1.5rem;
			color: #4e5f73;
		}
	</style>
</head>
<body>
<main class="login-shell">
	<section class="login-card">
		<h1><?= e($site_name) ?></h1>
		<p>Sign in to continue.</p>
		<?= $this->fetchSlot('content'); ?>
	</section>
</main>
<?= $this->fetchSlot('page_chrome'); ?>
<?= $this->getRenderer()->getJs(); ?>
</body>
</html>
