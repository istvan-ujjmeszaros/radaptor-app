#!/usr/bin/env bash
set -euo pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$REPO_ROOT"

fail() {
	echo "$*" >&2
	exit 1
}

for local_file in radaptor.local.json radaptor.local.lock.json; do
	if git ls-files --error-unmatch "$local_file" >/dev/null 2>&1; then
		fail "Local override file must not be tracked: $local_file"
	fi
done

for generated_file in \
	generated/__autoload__.php \
	generated/__package_assets__.json \
	generated/__templates__.php \
	generated/__themed_templates__.php
do
	if git ls-files --error-unmatch "$generated_file" >/dev/null 2>&1; then
		fail "Path-sensitive generated file must not be tracked: $generated_file"
	fi
done

php -r '
$repo_root = $argv[1];
$manifest_path = $argv[2];
$lock_path = $argv[3];

$fail = static function (string $message): never {
	fwrite(STDERR, $message . PHP_EOL);
	exit(1);
};

if (is_file($manifest_path)) {
	$raw_manifest = file_get_contents($manifest_path);

	if ($raw_manifest === false) {
		$fail("Unable to read manifest: {$manifest_path}");
	}

	$manifest = json_decode($raw_manifest, true);

	if (!is_array($manifest)) {
		$fail("Unable to decode manifest JSON: {$manifest_path}");
	}

	$violations = [];

	foreach (["core", "themes"] as $section) {
		$entries = $manifest[$section] ?? [];

		if (!is_array($entries)) {
			continue;
		}

		foreach ($entries as $package_id => $package) {
			if (!is_array($package)) {
				continue;
			}

			$source = $package["source"] ?? null;

			if (is_array($source) && ($source["type"] ?? null) === "dev") {
				$violations[] = $section . "." . $package_id;
			}
		}
	}

	if ($violations !== []) {
		sort($violations);
		$fail(
			"Committed radaptor.json must stay registry-first; first-party dev sources are not allowed: "
			. implode(", ", $violations)
		);
	}
}

if (is_file($lock_path)) {
	$lock_content = file_get_contents($lock_path);

	if ($lock_content === false) {
		$fail("Unable to read lockfile: {$lock_path}");
	}

	if (str_contains($lock_content, "/workspace/packages-dev/")) {
		$fail("Committed radaptor.lock.json must not contain /workspace/packages-dev/ paths.");
	}
}

$tracked_generated = [];
$exit_code = 0;
exec("git -C " . escapeshellarg($repo_root) . " ls-files generated", $tracked_generated, $exit_code);

if ($exit_code !== 0) {
	$fail("Unable to list tracked generated files.");
}

foreach ($tracked_generated as $relative_path) {
	$generated_path = $repo_root . "/" . $relative_path;

	if (!is_file($generated_path)) {
		continue;
	}

	$content = @file_get_contents($generated_path);

	if ($content === false) {
		continue;
	}

	foreach (["/workspace/packages-dev/", "packages/dev/", "/app/packages/"] as $marker) {
		if (str_contains($content, $marker)) {
			$fail(
				"Tracked generated file must not contain package dev paths: {$relative_path} ({$marker})"
			);
		}
	}
}
' "$REPO_ROOT" "$REPO_ROOT/radaptor.json" "$REPO_ROOT/radaptor.lock.json"
