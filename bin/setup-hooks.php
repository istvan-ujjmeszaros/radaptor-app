<?php

declare(strict_types=1);

$repoRoot = dirname(__DIR__);

if (!is_dir($repoRoot . DIRECTORY_SEPARATOR . '.githooks')) {
	fwrite(STDERR, "setup-hooks: .githooks directory not found, skipping.\n");

	exit(0);
}

$gitConfigPath = $repoRoot . DIRECTORY_SEPARATOR . '.git' . DIRECTORY_SEPARATOR . 'config';

if (!is_file($gitConfigPath)) {
	fwrite(STDERR, "setup-hooks: .git/config not found, skipping.\n");

	exit(0);
}

$command = sprintf(
	'git config --file %s core.hooksPath %s',
	escapeshellarg($gitConfigPath),
	escapeshellarg('.githooks')
);

exec($command, $output, $status);

if ($status !== 0) {
	fwrite(STDERR, "setup-hooks: failed to configure git hooks (status {$status}).\n");

	exit($status);
}

echo "setup-hooks: configured core.hooksPath -> .githooks\n";
