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

python3 - "$REPO_ROOT" "$REPO_ROOT/radaptor.json" "$REPO_ROOT/radaptor.lock.json" <<'PY'
import json
import pathlib
import subprocess
import sys

repo_root = pathlib.Path(sys.argv[1])
manifest_path = pathlib.Path(sys.argv[2])
lock_path = pathlib.Path(sys.argv[3])

if manifest_path.is_file():
    manifest = json.loads(manifest_path.read_text())
    violations: list[str] = []

    for section in ("core", "themes"):
        entries = manifest.get(section) or {}
        if not isinstance(entries, dict):
            continue

        for package_id, package in entries.items():
            if not isinstance(package, dict):
                continue
            source = package.get("source")
            if isinstance(source, dict) and source.get("type") == "dev":
                violations.append(f"{section}.{package_id}")

    if violations:
        joined = ", ".join(sorted(violations))
        raise SystemExit(
            f"Committed radaptor.json must stay registry-first; first-party dev sources are not allowed: {joined}"
        )

if lock_path.is_file():
    content = lock_path.read_text()
    if "/workspace/packages-dev/" in content:
        raise SystemExit("Committed radaptor.lock.json must not contain /workspace/packages-dev/ paths.")

tracked_generated = subprocess.check_output(
    ["git", "-C", str(repo_root), "ls-files", "generated"],
    text=True,
).splitlines()

for relative_path in tracked_generated:
    generated_path = repo_root / relative_path

    if not generated_path.is_file():
        continue

    try:
        content = generated_path.read_text()
    except UnicodeDecodeError:
        continue

    for marker in ("/workspace/packages-dev/", "packages/dev/", "/app/packages/"):
        if marker in content:
            raise SystemExit(
                f"Tracked generated file must not contain package dev paths: {relative_path} ({marker})"
            )
PY
