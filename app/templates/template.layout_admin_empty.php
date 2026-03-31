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
		body {
			margin: 0;
			color: #e2e8f0;
			background: #060816;
			font-family: "Segoe UI", "Helvetica Neue", Arial, sans-serif;
		}
		.login-shell {
			min-height: 100vh;
			display: flex;
			align-items: center;
			justify-content: center;
			padding: 2rem 1rem;
			background:
				radial-gradient(circle at top left, rgba(139, 92, 246, 0.3) 0%, rgba(139, 92, 246, 0) 40%),
				radial-gradient(circle at bottom right, rgba(14, 165, 233, 0.16) 0%, rgba(14, 165, 233, 0) 36%),
				linear-gradient(180deg, #0b1020 0%, #060816 100%);
		}
		.login-card {
			width: min(100%, 28rem);
			padding: 2.25rem;
			background: rgba(15, 23, 42, 0.84);
			border: 1px solid rgba(148, 163, 184, 0.18);
			border-radius: 1.25rem;
			box-shadow: 0 2rem 5rem rgba(2, 6, 23, 0.48);
			backdrop-filter: blur(18px);
		}
		.login-card h1 {
			margin: 0 0 0.35rem;
			font-size: 1.9rem;
			color: #f8fafc;
			letter-spacing: -0.03em;
		}
		.login-card p {
			margin: 0 0 1.75rem;
			color: rgba(226, 232, 240, 0.8);
		}
		.login-card .h4,
		.login-card .form-label {
			color: #f8fafc;
		}
		.login-card .form-control {
			background: rgba(15, 23, 42, 0.92);
			border-color: rgba(139, 92, 246, 0.38);
			color: #f8fafc;
			box-shadow: none;
		}
		.login-card .form-control::placeholder {
			color: rgba(148, 163, 184, 0.72);
		}
		.login-card .form-control:focus {
			border-color: rgba(196, 181, 253, 0.88);
			box-shadow: 0 0 0 0.24rem rgba(139, 92, 246, 0.22);
		}
		.login-card .btn-primary {
			border: 0;
			background: linear-gradient(135deg, #8b5cf6 0%, #ec4899 100%);
			box-shadow: 0 0.75rem 1.75rem rgba(139, 92, 246, 0.28);
		}
		.login-card .btn-primary:hover,
		.login-card .btn-primary:focus {
			background: linear-gradient(135deg, #7c3aed 0%, #db2777 100%);
		}
	</style>
</head>
<body>
<?= $this->getRenderer()->fetchInnerHtml(); ?>
<main class="login-shell">
	<section class="login-card">
		<h1><?= e($site_name) ?></h1>
		<p>Sign in to continue.</p>
		<?= $this->fetchSlot('content'); ?>
	</section>
</main>
<?= $this->fetchSlot('page_chrome'); ?>
<?= $this->getRenderer()->getJs(); ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?= $this->getRenderer()->fetchClosingHtml(); ?>
</body>
</html>
