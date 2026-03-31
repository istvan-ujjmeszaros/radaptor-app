<?php assert(isset($this) && $this instanceof Template); ?>
<?php if (class_exists('LibrariesRadaptorPortalAdmin')): ?>
	<?php if ($this->isEditable()): ?>
		<?php $this->registerLibrary('__RADAPTOR_PORTAL_ADMIN_SITE'); ?>
	<?php else: ?>
		<?php $this->registerLibrary('__RADAPTOR_PORTAL_ADMIN_BASE'); ?>
	<?php endif; ?>
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
	<?= $this->getRenderer()->getJsTop(); ?>
	<style>
		body {
			margin: 0;
			color: #e2e8f0;
			background: #060816;
			font-family: "Segoe UI", "Helvetica Neue", Arial, sans-serif;
		}
		.public-shell {
			min-height: 100vh;
			display: flex;
			align-items: center;
			justify-content: center;
			padding: 3rem 1.25rem;
			background:
				radial-gradient(circle at top left, rgba(139, 92, 246, 0.28) 0%, rgba(139, 92, 246, 0) 38%),
				radial-gradient(circle at bottom right, rgba(14, 165, 233, 0.18) 0%, rgba(14, 165, 233, 0) 34%),
				linear-gradient(180deg, #0b1020 0%, #060816 100%);
		}
		.public-card {
			width: min(100%, 44rem);
			padding: 2.75rem;
			background: rgba(15, 23, 42, 0.82);
			border: 1px solid rgba(148, 163, 184, 0.18);
			border-radius: 1.5rem;
			box-shadow: 0 2rem 5rem rgba(2, 6, 23, 0.48);
			backdrop-filter: blur(18px);
		}
		.public-card > *:first-child {
			margin-top: 0;
		}
		.public-card > *:last-child {
			margin-bottom: 0;
		}
		.public-card h1 {
			margin: 0 0 1rem;
			font-size: clamp(2rem, 4vw, 3rem);
			line-height: 1.05;
			color: #f8fafc;
			letter-spacing: -0.03em;
		}
		.public-card p {
			margin: 0 0 1rem;
			font-size: 1.05rem;
			line-height: 1.7;
			color: rgba(226, 232, 240, 0.88);
		}
		.public-card a {
			color: #c4b5fd;
			font-weight: 600;
			text-decoration: none;
		}
		.public-card a:hover {
			color: #e9d5ff;
			text-decoration: underline;
		}
	</style>
</head>
<body>
<?= $this->getRenderer()->fetchInnerHtml(); ?>
<main class="public-shell">
	<section class="public-card">
		<?= $this->fetchSlot('content'); ?>
	</section>
</main>
<?= $this->fetchSlot('page_chrome'); ?>
<?= $this->getRenderer()->getJs(); ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?= $this->getRenderer()->fetchClosingHtml(); ?>
</body>
</html>
