# Radaptor App — Agent Rules

## Project Overview

This is `radaptor-app-skeleton`, a registry-first consumer application built on the Radaptor framework.
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
3. Start the package-dev runtime with `./bin/docker-compose-packages-dev.sh radaptor-app-skeleton up -d --build`.
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
- Host-side workflow is Git-only. Hooks and helper scripts must dispatch every non-Git check into the supported container; never require host PHP, Composer, Python, php-cs-fixer, or Radaptor CLI.
- App-local transient QA outputs belong under `tmp/`. Do not leave `playwright-report/`, `test-results/`, proof clones, restore sandboxes, or scratch verification directories at repo root.

## Runtime Response & Message Rules

- New or touched runtime/user-facing messages must use i18n keys through `t()`. Do not hardcode visible message text in PHP, templates, JavaScript, CLI/API payloads, `SystemMessages`, or `ApiError` messages.
- Use `./radaptor.sh i18n:scan-hardcoded --json` to find visible UI literals in supported templates (`.php`, `.blade.php`, `.twig`) that bypass i18n keys. These are warnings by default; `i18n:doctor` exposes them as `hardcoded_ui` and only fails on them with `--strict-hardcoded`.
- Service/model/form code must not write `SystemMessages` in new or touched code. API, JSON, HTMX, MCP, CLI-web, and other non-HTML flows must return structured response data or headers instead of session messages.
- Full-page classic web events may temporarily map service Result values to `SystemMessages` at the call site only.
- Use `Request::wantsNonHtmlResponse()` for response-family detection. Do not hand-read `HTTP_ACCEPT`, `HTTP_X_REQUESTED_WITH`, or `HTTP_HX_REQUEST`, and do not add query-parameter fallbacks such as `ajax=1`.
- For HTMX admin flows, use header-detected server-rendered fragments and stable swap targets rather than query-parameter pseudo-routing. If an OOB swap inserts new `hx-*` markup outside the original target, verify whether the current HTMX runtime processes it automatically; if not, explicitly process the inserted root and cover it with a browser smoke.
- Namespace framework/editor DOM ids away from feature component ids (`edit-*` for editor wrappers is preferred) so HTMX targets, OOB swaps, labels, and custom selectors do not collide.
- When touching framework/CMS PHP files that can inspect response-family headers, add them to the relevant `phpstan.neon` `paths` entry so the response-detection rule checks them.
- `ApiError` may be used as a domain/service Result value object; `ApiResponse` remains the boundary renderer.

## Worktree Isolation Rule

- Git worktrees must stay registry-first. Do not commit first-party `dev` sources in `radaptor.json`.
- First-party package modifications happen in `/apps/_RADAPTOR/packages-dev/...`, not inside a worktree copy of the app.
- If a feature branch also needs framework/CMS/theme changes, make separate repo-local commits/PRs in the affected package repo.
- After opening or updating a GitHub PR, request Codex review with a PR comment containing exactly `@codex review`. Do not use GitHub's normal reviewer API for `codex`; an `eyes` reaction means the bot accepted the request, not that review is complete.

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
- `../bin/cleanup-workspace-ephemera.sh --phase registry-first`
- `docker compose -f docker-compose-dev.yml up -d --build`
- `docker compose -f docker-compose-dev.yml exec -T -e XDEBUG_MODE=off php phpunit`
- `docker compose -f docker-compose-dev.yml exec -T -e XDEBUG_MODE=off php phpstan analyze`
- `docker compose -f docker-compose-dev.yml exec -T php bash -lc 'cd /app && ./php-cs-fixer.sh --config=.php-cs-fixer.php'`
- `./bin/docker-compose-packages-dev.sh radaptor-app-skeleton exec -T php bash -lc 'cd /workspace/packages-dev/core/framework && /app/php-cs-fixer.sh --config=.php-cs-fixer.php'`
- `./bin/docker-compose-packages-dev.sh radaptor-app-skeleton exec -T php bash -lc 'cd /workspace/packages-dev/core/cms && /app/php-cs-fixer.sh --config=.php-cs-fixer.php'`
- `./bin/docker-compose-packages-dev.sh radaptor-app-skeleton exec -T -e XDEBUG_MODE=off php vendor/bin/phpstan analyse -a /workspace/packages-dev/core/framework/classes/phpstan/class.NonHtmlResponseHeaderDetectionRule.php -c /workspace/packages-dev/core/framework/phpstan.neon`
- `./bin/docker-compose-packages-dev.sh radaptor-app-skeleton exec -T -e XDEBUG_MODE=off php vendor/bin/phpstan analyse -a /workspace/packages-dev/core/framework/classes/phpstan/class.NonHtmlResponseHeaderDetectionRule.php -c /workspace/packages-dev/core/cms/phpstan.neon`

## Admin/Login Browser Checks

- For admin or login visual bugs, open the exact reported URL in a clean logged-out Playwright session first. Do not replace it with `/admin/index.html`, `/login.html`, a different port, or the default app URL.
- Confirm which compose project owns the port and whether `radaptor.local.json` package-dev overrides are active before editing framework/CMS/theme files.
- ACL-protected admin URLs may render the configured login page at the same URL with HTTP 403 for anonymous users. That 403 is expected login fallback behavior unless it remains after logging in with an authorized user.
- The fallback login output comes from the configured login webpage, usually `/login.html`, and that page's own layout/widgets. Use `./radaptor.sh webpage:info /login.html --json` before assuming the layout is `admin_empty`; it may be `admin_nomenu` or another configured layout.
