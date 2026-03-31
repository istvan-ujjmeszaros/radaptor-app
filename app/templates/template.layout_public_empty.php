<?php assert(isset($this) && $this instanceof Template); ?>
<?php if (class_exists('LibrariesRadaptorPortalAdmin')): ?>
	<?php $this->registerLibrary('__RADAPTOR_PORTAL_ADMIN_BASE'); ?>
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
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="shortcut icon" href="/favicon.ico">
	<link rel="icon" href="/favicon.ico" type="image/x-icon">
	<?= $this->getRenderer()->getCss(); ?>
	<style>
		.public-shell {
			min-height: 100vh;
			display: grid;
			place-items: center;
			padding: 2rem 1rem;
			background: radial-gradient(circle at top, #eff4fa 0%, #ffffff 55%);
		}
		.public-card {
			width: min(100%, 44rem);
			padding: 2.5rem;
			background: #fff;
			border-radius: 1.25rem;
			box-shadow: 0 1.25rem 3rem rgba(34, 56, 78, 0.12);
		}
	</style>
</head>
<body>
<main class="public-shell">
	<section class="public-card">
		<?= $this->fetchSlot('content'); ?>
	</section>
</main>
<?= $this->fetchSlot('page_chrome'); ?>
<?= $this->getRenderer()->getJs(); ?>
</body>
</html>
