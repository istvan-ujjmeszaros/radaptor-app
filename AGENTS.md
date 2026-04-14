# Radaptor App — Agent Rules

## Project Overview

This is `radaptor-app`, a registry-first consumer application built on the Radaptor framework.
It uses 4 first-party packages (framework, CMS, 2 themes), each with its own GitHub repository.
Packages are consumed either from the public registry (default, production-safe) or from local
dev checkouts for active development.

## Package Architecture

### Two modes

- **registry mode** (`packages/registry/...`): Version-pinned, read-only package installed from
  the registry. This is the default, production-safe state. The `radaptor.lock.json` records
  the exact version and hash.
- **dev mode** (`packages/dev/...`): Local Git checkout, editable. Used to develop and test
  first-party packages (framework, CMS, themes) in the context of this consumer app.

### Dev mode workflow

1. The package lives under `packages/dev/<type>/<id>/` as a nested Git repo (its own `.git`)
2. In `radaptor.json`, the package source is set to `"type": "dev"` + `"path"`
3. Development and testing happen inside this consumer app
4. When ready: PR to the package's own GitHub repo -> code review -> merge
5. After merge: publish a new version (`./radaptor.sh package:release <key> --json`)
6. Switch the consumer app back to registry mode: update `radaptor.json` source -> `./radaptor.sh update --json`

### Manifest files

- `radaptor.json` = source configuration (which package runs in dev vs. registry mode)
- `radaptor.lock.json` = lockfile (exact versions and hashes for reproducible installs)

### Source of truth

- For first-party packages: the **package's own GitHub repo main branch**
- The `packages/dev/` content is a temporary development copy — it may be newer or older than GitHub
- NEVER assume `packages/dev/` content is canonical without verifying against the GitHub remote

### Current package state

| Package | Mode | Path |
|---|---|---|
| core/framework | registry | `packages/registry/core/framework/` |
| core/cms | registry | `packages/registry/core/cms/` |
| themes/portal-admin | registry | `packages/registry/themes/portal-admin/` |
| themes/so-admin | dev | `packages/dev/themes/so-admin/` |

## Worktree Isolation Rule

- Git worktree-s MUST NOT use `packages/dev/` in dev mode — only registry packages
- First-party package modifications happen exclusively in the main `radaptor-app/` working copy
- When creating a worktree, `radaptor.json` / `radaptor.lock.json` must not be set to dev sources
- If a feature branch also needs module changes: create a separate PR in the module repo,
  developed and tested in the main working copy, not in the worktree

## Destructive Operations Safety

- Before any delete/overwrite operation (rsync --delete, rm -rf, force-push, reset --hard):
  run `git fetch && git diff origin/main` in every affected repo
- If the diff is non-empty or contains unexpected changes: **STOP and report to the maintainer**
- NEVER treat `packages/dev/` content as source of truth for first-party packages
- If a `packages/dev/` directory has lost its `.git` state, that does NOT mean its content is
  up-to-date with the GitHub repo — the `.git` loss may have occurred before the latest GitHub
  merges. Always verify against the remote before any overlay or sync operation.

This rule exists because a real incident destroyed merged, working email queue code: a restore
script assumed app-local content was canonical, but GitHub had newer merged PRs not reflected
locally. The `rsync --delete` wiped them out.

## Commit & Pull Request Guidelines

- Independent PRs (different repos, different files, no dependency) can be batch-merged
- Dependent PRs must be merged one at a time, in order, with verification after each:
  - PRs that modify the same files or repo
  - PRs where one builds on changes from another (e.g., framework PR required by CMS PR)
  - PRs that change schema or lockfiles that subsequent PRs depend on
- Example: framework PR #10 + CMS PR #8 are dependent -> merge framework first, verify, then CMS

## Agent Communication Rules

- In plans and summaries, use **concrete paths**, not abstract jargon
- Instead of "the app-local content": write "the current content of `packages/dev/core/framework/`"
- If a term might be unclear, define it inline or reference `meta/radaptor-developer-reference.md`
- If an expression is ambiguous, that is the plan's fault, not the maintainer's — fix it

## Verification

- `bin/check-repo-baseline.sh`: repo baseline + formatting check
- CI: `.github/workflows/repo-checks.yml` runs the same check
- PHPUnit: `docker compose -f docker-compose-dev.yml exec -T -e XDEBUG_MODE=off php phpunit`
- PHPStan: `docker compose -f docker-compose-dev.yml exec -T -e XDEBUG_MODE=off php phpstan analyze`
