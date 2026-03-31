<?php

declare(strict_types=1);

$repoRoot = trim(shell_exec('git rev-parse --show-toplevel 2>/dev/null') ?? '');

if ($repoRoot === '') {
    fwrite(STDERR, "setup-hooks: not a git repository, skipping.\n");
    exit(0);
}

if (!is_dir($repoRoot . DIRECTORY_SEPARATOR . '.githooks')) {
    fwrite(STDERR, "setup-hooks: .githooks directory not found, skipping.\n");
    exit(0);
}

chdir($repoRoot);

exec('git config core.hooksPath .githooks', $output, $status);

if ($status !== 0) {
    fwrite(STDERR, "setup-hooks: failed to configure git hooks (status {$status}).\n");
    exit($status);
}

echo "setup-hooks: configured core.hooksPath -> .githooks\n";
