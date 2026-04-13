<?php

declare(strict_types=1);

$repoRoot = dirname(__DIR__);
$installScript = $repoRoot . DIRECTORY_SEPARATOR . '.githooks' . DIRECTORY_SEPARATOR . 'install.sh';

if (!is_file($installScript)) {
	fwrite(STDERR, "setup-hooks: .githooks/install.sh not found, skipping.\n");

	exit(0);
}

$gitPath = $repoRoot . DIRECTORY_SEPARATOR . '.git';

if (!is_dir($gitPath) && !is_file($gitPath)) {
	fwrite(STDERR, "setup-hooks: .git entry not found, skipping.\n");

	exit(0);
}

exec('command -v bash >/dev/null 2>&1', $output, $status);

if ($status !== 0) {
	fwrite(STDERR, "setup-hooks: bash binary not available, skipping.\n");

	exit(0);
}

$command = sprintf(
	'cd %s && bash %s',
	escapeshellarg($repoRoot),
	escapeshellarg('./.githooks/install.sh')
);

passthru($command, $status);

if ($status !== 0) {
	fwrite(STDERR, "setup-hooks: install script failed (status {$status}).\n");

	exit($status);
}
