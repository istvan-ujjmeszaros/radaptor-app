# Radaptor App Notes

The canonical repo workflow rules live in [`AGENTS.md`](./AGENTS.md). This file is the short
app-specific version for future sessions.

## Package Modes

- Registry-first is the default, committed state:
  - manifest: `radaptor.json`
  - lockfile: `radaptor.lock.json`
- Maintainer-local first-party package development uses:
  - `radaptor.local.json`
  - `radaptor.local.lock.json`
  - `./bin/docker-compose-packages-dev.sh radaptor-app ...`

## Editable Package Repos

Editable first-party repos are no longer app-local nested checkouts. They live at workspace root:

- `/apps/_RADAPTOR/packages-dev/core/framework`
- `/apps/_RADAPTOR/packages-dev/core/cms`
- `/apps/_RADAPTOR/packages-dev/themes/portal-admin`
- `/apps/_RADAPTOR/packages-dev/themes/so-admin`

Inside the package-dev Docker runtime they are mounted under:

- `/workspace/packages-dev/core/framework`
- `/workspace/packages-dev/core/cms`
- `/workspace/packages-dev/themes/portal-admin`
- `/workspace/packages-dev/themes/so-admin`

Do not treat stale app-local `packages/dev/...` content as source of truth.

## Runtime Rules

- `docker-compose-dev.yml` must stay standalone and registry-first.
- Package-dev mode is opt-in through the workspace compose override:
  - `docker-compose.packages-dev.yml`
- If `radaptor.local.json` exists but `RADAPTOR_DEV_ROOT` is not enabled, bootstrap/CLI should
  fail hard instead of guessing.
- New or touched visible runtime messages must use i18n keys through `t()`; do not hardcode message text in PHP, templates, JavaScript, `SystemMessages`, or `ApiError`.
- Non-HTML flows (API/JSON/HTMX/MCP/CLI-web) return structured responses or headers and must not write `SystemMessages`.
- Use `Request::wantsNonHtmlResponse()` for response-family detection; do not add `ajax=1` or manual header parsing outside the helper.
- When touching framework/CMS PHP files that can inspect response-family headers, add them to the relevant `phpstan.neon` `paths` entry so the response-detection rule checks them.

## Generated Files

Only these generated files are intentionally untracked because they embed path-sensitive package
roots:

- `generated/__autoload__.php`
- `generated/__package_assets__.json`
- `generated/__templates__.php`
- `generated/__themed_templates__.php`

The rest of `generated/` remains tracked, including `generated/__config__.php` and
`generated/__db_schema_data__.php`.

## Verification

- PHPUnit:
  - `docker compose -f docker-compose-dev.yml exec -T -e XDEBUG_MODE=off php phpunit`
- PHPStan:
  - `docker compose -f docker-compose-dev.yml exec -T -e XDEBUG_MODE=off php phpstan analyze`
- Framework package PHPStan from the supported package-dev runtime:
  - `./bin/docker-compose-packages-dev.sh radaptor-app exec -T -e XDEBUG_MODE=off php vendor/bin/phpstan analyse -a /workspace/packages-dev/core/framework/classes/phpstan/class.NonHtmlResponseHeaderDetectionRule.php -c /workspace/packages-dev/core/framework/phpstan.neon`
- CMS package PHPStan from the supported package-dev runtime:
  - `./bin/docker-compose-packages-dev.sh radaptor-app exec -T -e XDEBUG_MODE=off php vendor/bin/phpstan analyse -a /workspace/packages-dev/core/framework/classes/phpstan/class.NonHtmlResponseHeaderDetectionRule.php -c /workspace/packages-dev/core/cms/phpstan.neon`
