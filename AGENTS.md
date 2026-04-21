# Radaptor App — Agent Rules

## Project Overview

This is `radaptor-app`, a registry-first consumer application built on the Radaptor framework.
It uses 4 first-party packages (framework, CMS, `portal-admin`, `so-admin`), each with its own
GitHub repository.

Committed state is always registry-first:
- `radaptor.json`
- `radaptor.lock.json`

Maintainer-local dev state is opt-in and gitignored:
- `radaptor.local.json`
- `radaptor.local.lock.json`

## First-Party Package Workflow

### Canonical editable repos

First-party editable repos live outside the app tree:
- host path: `/apps/_RADAPTOR/packages-dev/core/framework`
- host path: `/apps/_RADAPTOR/packages-dev/core/cms`
- host path: `/apps/_RADAPTOR/packages-dev/themes/portal-admin`
- host path: `/apps/_RADAPTOR/packages-dev/themes/so-admin`

Inside Docker, the same repos are visible under the workspace package-dev compose override:
- `/workspace/packages-dev/core/framework`
- `/workspace/packages-dev/core/cms`
- `/workspace/packages-dev/themes/portal-admin`
- `/workspace/packages-dev/themes/so-admin`

### Two states

- `packages/registry/...`: immutable installed runtime content managed by `install` / `update`
- `packages-dev/...`: Git-owned editable source repos managed only by Git

### Local override workflow

1. Keep committed `radaptor.json` registry-first.
2. Put maintainer-local overrides into gitignored `radaptor.local.json`.
3. Start the package-dev runtime with `./bin/docker-compose-packages-dev.sh radaptor-app up -d --build`.
4. Point first-party package overrides at logical `location` values under `RADAPTOR_DEV_ROOT`.
5. Let `install` / `update` write only `radaptor.local.lock.json` while local overrides are active.

Example:

```json
{
  "core": {
    "framework": { "source": { "type": "dev", "location": "core/framework" } },
    "cms": { "source": { "type": "dev", "location": "core/cms" } }
  },
  "themes": {
    "portal-admin": { "source": { "type": "dev", "location": "themes/portal-admin" } }
  }
}
```

## Hard Rules

- Committed `radaptor.json` must stay registry-first under `core` and `themes`.
- `radaptor.local.json` and `radaptor.local.lock.json` must never be tracked.
- `radaptor.json` is the only committed source selector; `radaptor.local.json` is the only supported local override file.
- `packages/registry/...` and `packages-dev/...` must never be connected with symlinks.
- `./radaptor.sh install` and `./radaptor.sh update` may only create, delete, or overwrite content under `packages/registry/...`.
- `packages-dev/...` is Git-owned working state. Only Git operations may change it.
- If local overrides are active, committed `radaptor.lock.json` must remain unchanged; only `radaptor.local.lock.json` may be written.
- If `radaptor.local.json` exists but the package-dev compose override is not active, bootstrap/CLI must fail hard instead of guessing a dev root.
- Bootstrap proof and registry-first validation must run with `RADAPTOR_DISABLE_LOCAL_OVERRIDES=1`.

## Worktree Isolation Rule

- Git worktrees must stay registry-first. Do not commit first-party `dev` sources in `radaptor.json`.
- First-party package modifications happen in `/apps/_RADAPTOR/packages-dev/...`, not inside a worktree copy of the app.
- If a feature branch also needs framework/CMS/theme changes, make separate repo-local commits/PRs in the affected package repo.

## Destructive Operations Safety

- Before any delete/overwrite operation against a first-party repo, run `git fetch && git diff origin/main` in that repo.
- NEVER treat stale files under an app-local `packages/dev/...` directory as canonical source.
- If a registry package path resolves to an editable checkout, or if anything under `packages/registry/...` is a symlink, stop and report corrupted state.

## Repo Baseline Minimums

- This repo keeps the tracked baseline files for the `php-consumer-app` profile.
- The worktree must have `core.hooksPath=.githooks`.
- This is a PHP-heavy repo, so it must keep:
  - `.php-cs-fixer.php`
  - `phpstan.neon`
- The local-override guard is part of the baseline and must stay enabled.

## Verification

- `bin/check-repo-baseline.sh`
- `docker compose -f docker-compose-dev.yml up -d --build`
- `docker compose -f docker-compose-dev.yml exec -T -e XDEBUG_MODE=off php phpunit`
- `docker compose -f docker-compose-dev.yml exec -T -e XDEBUG_MODE=off php phpstan analyze`
- `docker compose -f docker-compose-dev.yml exec -T php bash -lc 'cd /app && ./php-cs-fixer.sh --config=.php-cs-fixer.php'`
- `./bin/docker-compose-packages-dev.sh radaptor-app exec -T php bash -lc 'cd /workspace/packages-dev/core/framework && /app/php-cs-fixer.sh --config=.php-cs-fixer.php'`
- `./bin/docker-compose-packages-dev.sh radaptor-app exec -T php bash -lc 'cd /workspace/packages-dev/core/cms && /app/php-cs-fixer.sh --config=.php-cs-fixer.php'`
- `./bin/docker-compose-packages-dev.sh radaptor-app exec -T -e XDEBUG_MODE=off php vendor/bin/phpstan analyse -c /workspace/packages-dev/core/framework/phpstan.neon`
- `./bin/docker-compose-packages-dev.sh radaptor-app exec -T -e XDEBUG_MODE=off php vendor/bin/phpstan analyse -c /workspace/packages-dev/core/cms/phpstan.neon`
