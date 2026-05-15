# Radaptor App Notes

The canonical repo workflow rules live in [`AGENTS.md`](./AGENTS.md). This file is the short
Claude-facing version for future sessions. Treat `AGENTS.md` as source of truth.

## Package Modes

- Registry-first is the default, committed state:
  - manifest: `radaptor.json`
  - lockfile: `radaptor.lock.json`
- Maintainer-local first-party package development uses gitignored:
  - `radaptor.local.json`
  - `radaptor.local.lock.json`
  - Runtime: `./bin/docker-compose-packages-dev.sh radaptor-app-skeleton ...`

## Editable Package Repos

Editable first-party repos live in the app-local, gitignored package-dev root and are mounted into
the package-dev runtime:

- `packages-dev/core/framework` → `/workspace/packages-dev/core/framework`
- `packages-dev/core/cms` → `/workspace/packages-dev/core/cms`
- `packages-dev/themes/portal-admin` → `/workspace/packages-dev/themes/portal-admin`
- `packages-dev/themes/so-admin` → `/workspace/packages-dev/themes/so-admin`

Do not treat stale app-local `packages/dev/...` content as source of truth.

## Hard Rules

- Committed `radaptor.json` must stay registry-first under `core` and `themes`.
- `radaptor.local.json` / `radaptor.local.lock.json` must never be tracked.
- `packages/registry/...` and `packages-dev/...` must never be connected with symlinks.
- `./radaptor.sh install` / `update` may only mutate `packages/registry/...`.
- `packages-dev/...` is Git-owned; only Git operations may change it.
- If `radaptor.local.json` exists but the package-dev compose override is not active, bootstrap/CLI
  must fail hard instead of guessing a dev root.
- Bootstrap proof and registry-first validation must run with `RADAPTOR_DISABLE_LOCAL_OVERRIDES=1`.
- Host-side workflow is Git-only. Hooks/helpers dispatch every non-Git check into the supported
  container; never require host PHP, Composer, Python, php-cs-fixer, or Radaptor CLI.
- App-local transient QA outputs (`playwright-report/`, `test-results/`, proof clones, scratch
  dirs) belong under `tmp/`, not at repo root.

## Supported Runtime Rule

- All PHP/Composer/Radaptor CLI work runs inside the `php` container.
- Default bring-up: `docker compose -f docker-compose-dev.yml up -d --build`. Do not bring the app
  up with a handpicked subset unless a task explicitly needs it.
- Queue worker service: `swoole-queue-worker`. Swoole is built into the `php` image; no separate
  `swoole` service.

## Runtime Response, I18n, HTMX

- New or touched visible runtime messages must use i18n keys through `t()`. No hardcoded text in
  PHP, templates, JavaScript, CLI/API payloads, `SystemMessages`, or `ApiError`.
- Use `./radaptor.sh i18n:scan-hardcoded --json` to find hardcoded UI text in `.php`, `.blade.php`,
  `.twig`. Warnings by default; `i18n:doctor` surfaces as `hardcoded_ui` and only fails with
  `--strict-hardcoded`.
- Service/model/form code must not write `SystemMessages` in new or touched code. Non-HTML flows
  (API/JSON/HTMX/MCP/CLI-web) must return structured response data or headers.
- Full-page classic web events may map service Result values to `SystemMessages` at the call site only.
- Use `Request::wantsNonHtmlResponse()` for response-family detection. Do not hand-read
  `HTTP_ACCEPT`, `HTTP_X_REQUESTED_WITH`, `HTTP_HX_REQUEST`, or add `ajax=1` fallbacks.
- For HTMX admin flows, use header-detected server-rendered fragments and stable swap targets. If
  an OOB swap inserts new `hx-*` markup outside the original target, explicitly process the
  inserted root and cover with a browser smoke.
- Namespace framework/editor DOM ids away from feature component ids (`edit-*` for editor wrappers)
  to avoid HTMX target / label / selector collisions.
- When touching framework/CMS PHP files that can inspect response-family headers, add them to the
  relevant `phpstan.neon` `paths` entry so the detection rule actually checks them.
- `ApiError` may be used as a domain/service Result value object; `ApiResponse` is the boundary renderer.

## Worktree & Destructive Operations

- Git worktrees must stay registry-first. Do not commit first-party `dev` sources in `radaptor.json`.
- First-party package modifications happen in the app-local `packages-dev/...` nested repos, not in
  `packages/registry/...`. Separate repo-local commits/PRs in the affected package repo.
- Before any delete/overwrite against a first-party repo: `git fetch && git diff origin/main` first.
- NEVER treat stale files under an app-local `packages/dev/...` as canonical.
- If a registry package path resolves to an editable checkout, or anything under
  `packages/registry/...` is a symlink, stop and report corrupted state.

## Generated Files

Only these are gitignored because they embed path-sensitive package roots:
- `generated/__autoload__.php`
- `generated/__package_assets__.json`
- `generated/__templates__.php`
- `generated/__themed_templates__.php`

The rest of `generated/` remains tracked. After generator-affecting changes (new widgets, forms,
templates, entities, roles), run the relevant `./radaptor.sh build:*` command.

## Repo Baseline

- `php-consumer-app` profile baseline files must stay committed.
- Worktree must have `core.hooksPath=.githooks`.
- PHP-heavy repo, so keep `.php-cs-fixer.php` and `phpstan.neon`.
- The local-override guard is part of the baseline and must stay enabled.

## Verification

- `bin/check-repo-baseline.sh`
- `docker compose -f docker-compose-dev.yml exec -T -e XDEBUG_MODE=off php phpunit`
- `docker compose -f docker-compose-dev.yml exec -T -e XDEBUG_MODE=off php phpstan analyze`
- `docker compose -f docker-compose-dev.yml exec -T php bash -lc 'cd /app && ./php-cs-fixer.sh --config=.php-cs-fixer.php'`
- Framework PHPStan from package-dev runtime:
  - `./bin/docker-compose-packages-dev.sh radaptor-app-skeleton exec -T -e XDEBUG_MODE=off php vendor/bin/phpstan analyse -a /workspace/packages-dev/core/framework/classes/phpstan/class.NonHtmlResponseHeaderDetectionRule.php -c /workspace/packages-dev/core/framework/phpstan.neon`
- CMS PHPStan from package-dev runtime:
  - `./bin/docker-compose-packages-dev.sh radaptor-app-skeleton exec -T -e XDEBUG_MODE=off php vendor/bin/phpstan analyse -a /workspace/packages-dev/core/framework/classes/phpstan/class.NonHtmlResponseHeaderDetectionRule.php -c /workspace/packages-dev/core/cms/phpstan.neon`

## Layout rename gate (install / update / local-lock:refresh)

`radaptor install`, `radaptor update`, and `radaptor local-lock:refresh` detect
`deprecated_layouts` declarations in incoming package `.registry-package.json`
metadata and gate CMS-content mutation on a user decision. The gate runs AFTER
the registry packages have been installed (so the latest `.registry-package.json`
files are readable on disk) but BEFORE lockfile-write, asset-build, migrations,
and seeds. An abort therefore leaves the registry refreshed but the
follow-up steps unexecuted, and the CMS content (`attributes.resource_data.layout`
and `attributes._theme_settings.<layout>`) is untouched; the next run resumes the
gate.

- TTY: interactive `[y/N]` prompt listing affected webpages and `_theme_settings` rows.
- Non-interactive (CI, `--json`): pass one of
  - `--apply-layout-renames`    apply the listed renames inside an audited transaction;
  - `--abort-on-layout-renames` exit 1 before any content mutation.
  Without either flag the command exits 1 and prints the required flag.
- Applied renames write `cms_mutation_audit` rows with `operation='layout:rename'`
  and per-resource leaf rows (`layout:rename:webpage`, `layout:rename:theme_settings`,
  `layout:rename:theme_settings_conflict`). The audit table uses an independent PDO
  connection, so the rows survive transaction rollback in tests; tests should clean
  up rows they wrote.

## Admin/Login Browser Checks

- Open the exact reported URL in a clean logged-out Playwright session first. Do not substitute
  `/admin/index.html`, `/login.html`, a different port, or the default app URL.
- ACL-protected admin URLs may render the configured login page at the same URL with HTTP 403 for
  anonymous users — expected fallback unless it persists after login with an authorized user.
- The fallback login output comes from the configured login webpage, usually `/login.html`. Use
  `./radaptor.sh webpage:info /login.html --json` before assuming the layout is `admin_empty`; it
  may be `admin_nomenu`, `admin_login`, or another configured layout.

## Commit & PR

- Do not commit without explicit maintainer approval.
- After opening or updating a GitHub PR, add a PR comment containing exactly `@codex review`.
- Thread-aware review reads; resolve threads that pushed commits address; never resolve to clear
  the list. Re-check unresolved count before requesting another review, merging, or publishing.
- After publishing a first-party package, update dependent consumer lockfiles in separate commits.
