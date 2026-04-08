<?php

declare(strict_types=1);

$repoRoot = dirname(__DIR__);

if (!is_dir($repoRoot . DIRECTORY_SEPARATOR . '.githooks')) {
	fwrite(STDERR, "setup-hooks: .githooks directory not found, skipping.\n");

	exit(0);
}

$gitPath = $repoRoot . DIRECTORY_SEPARATOR . '.git';

if (!is_dir($gitPath) && !is_file($gitPath)) {
	fwrite(STDERR, "setup-hooks: .git entry not found, skipping.\n");

	exit(0);
}

exec('command -v git >/dev/null 2>&1', $output, $status);

if ($status !== 0) {
	fwrite(STDERR, "setup-hooks: git binary not available, skipping.\n");

	exit(0);
}

$command = sprintf(
	'git -C %s config core.hooksPath %s',
	escapeshellarg($repoRoot),
	escapeshellarg('.githooks')
);

exec($command, $output, $status);

if ($status !== 0) {
	fwrite(STDERR, "setup-hooks: failed to configure git hooks (status {$status}).\n");

	exit($status);
}

echo "setup-hooks: configured core.hooksPath -> .githooks\n";
