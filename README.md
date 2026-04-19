# Radaptor App

`radaptor-app` is the minimal, registry-first application skeleton for Radaptor.

It is intentionally small:
- default packages: `framework`, `cms`, `portal-admin`
- default plugins: none
- first-run data: one bootstrap admin user and a placeholder homepage

## Quick start

1. Build the local PHP platform image:
   - `./docker/build-php-platform.sh dev`
2. Start the local stack:
   - `docker compose -f docker-compose-dev.yml up -d --build`
3. Run CLI commands in the `php` container:
   - Docker Desktop path: open a terminal for the `php` container and run `bash`
   - shell shortcut: `./php-shell.sh`
   - direct shortcuts:
     - `./composer.sh install`
     - `./radaptor install --json`
4. Open the site:
   - homepage: `http://localhost/`
   - login: `http://localhost/login.html`
   - admin: `http://localhost/admin/index.html`

If you are running multiple local stacks in parallel, use `.env` to override ports and the compose
project name before `docker compose up`.

All supported CLI work happens inside Docker. Host PHP and host Composer are not part of the
supported workflow.

The `php` runtime image is intentionally thin. The slow PHP extension/tooling layer now lives in a
separate local platform image so routine runtime Dockerfile changes do not rebuild `swoole`,
`redis`, `brotli`, Composer, Phive, and `php-cs-fixer`.

Default ACL baseline after install:
- `/login.html` is an explicitly public special page
- `/admin/` is explicitly non-inheriting and admin-only
- `/` inherits a private root ACL baseline for logged-in users

Anonymous access to protected pages keeps the requested URL and renders the login page with `403`,
instead of redirecting to a different URL. Direct access to `/login.html` itself returns `200`.

What happens on first install:
- `docker compose up` gives you the supported PHP runtime
- `./radaptor install --json` bootstraps the pinned framework package if it is still missing
- then the framework CLI continues the normal install/update/build/migrate/seed flow
- the committed `radaptor.json` points at the default public package registry:
  `https://packages.radaptor.com/registry.json`
- `RADAPTOR_REGISTRY_URL` remains available as a local override for scratch registries or isolated
  testing

### Local override example

For a local registry and a second app instance, override the default registry in shell env or `.env`:

```bash
export RADAPTOR_REGISTRY_URL=http://host.docker.internal:8091/registry.json
export COMPOSE_PROJECT_NAME=radaptor-app-dev
export APP_HTTP_PORT=8085
export APP_HTTPS_PORT=8445
export APP_DB_PORT=3309
docker compose -f docker-compose-dev.yml up -d --build
```

### Parallel clone / playground example

If you want to validate a second copy without stopping an existing app instance, use a different
folder and give that copy its own compose project name and host ports via shell env or `.env`.

## Local PHP platform image

The PHP 8.5 stack is split into:
- a heavy local platform image built by `./docker/build-php-platform.sh`
- a thin runtime image used by `docker compose`

The default local platform tags are:
- `radaptor-app-php-platform:8.5-dev-local`
- `radaptor-app-php-platform:8.5-prod-local`

If you want to point the runtime at a different prebuilt base later, override:
- `RADAPTOR_PHP_PLATFORM_DEV_IMAGE`
- `RADAPTOR_PHP_PLATFORM_PROD_IMAGE`

Examples:

- build dev only: `./docker/build-php-platform.sh dev`
- build prod only: `./docker/build-php-platform.sh prod`
- build both: `./docker/build-php-platform.sh all`

## Default bootstrap credentials

The first `mandatory` app seed ensures a bootstrap admin user based on `.env`:
- `APP_BOOTSTRAP_ADMIN_USERNAME`
- `APP_BOOTSTRAP_ADMIN_PASSWORD`
- `APP_BOOTSTRAP_ADMIN_LOCALE`
- `APP_BOOTSTRAP_ADMIN_TIMEZONE`

Change the password after the first login.

## What is committed

This skeleton commits:
- `radaptor.json`
- `radaptor.lock.json`

The committed lockfile pins tested package versions. On first run, `radaptor.php install` uses the
locked `core.framework` package metadata and the configured registry URL to bootstrap the framework
package into `packages/registry/core/framework` before delegating into the framework CLI.

By default that registry URL is `https://packages.radaptor.com/registry.json`. Local development can
still override it with `RADAPTOR_REGISTRY_URL` or a local `.env`.
The first-run DB bootstrap currently relies on the MariaDB init schema shipped in `docker/mariadb/initdb.d/`.

### Maintainer note: immutable first-party package releases

When this skeleton is validated in registry-first mode, first-party package changes must be
released as new immutable versions before the consumer app is updated:

- `radaptor.json` is the only supported source selector
- `packages/registry/...` and `packages/dev/...` must never be connected with symlinks
- `install` / `update` may only touch `packages/registry/...`; `packages/dev/...` is Git-owned
- dev mode (`packages/dev/...`) does not need release/publish
- registry-first validation does need an immutable package release after first-party package changes
- the consumer app refresh stays the normal `./radaptor update --json`, but only after the
  registry deploy completed
- then run registry-first verification on the main app instance: no symlinks under
  `packages/registry/`, correct `radaptor.lock.json` `resolved.path` values, and a repeated
  `./radaptor update --json` that leaves `packages/dev/.../.git` intact

The supported maintainer path is:

- stable release: `./radaptor package:release <package-key> --json`
- prerelease: `./radaptor package:prerelease <package-key> --channel alpha|beta|rc --json`

After that:

- commit the bumped `.registry-package.json` in the package repo
- commit + push the `radaptor_plugin_registry` repo
- pushes to `radaptor_plugin_registry/main` auto-deploy to `https://packages.radaptor.com/`
- only then run `./radaptor update --json` so `radaptor.lock.json` and `packages/registry/...`
  pick up the new version

The low-level `package:publish` and `package:publish-all` commands remain available for internal or
bootstrap cases, but they are not the normal maintainer release workflow and they refuse to
overwrite an already published version.

`./bin/prove-radaptor-app-bootstrap.sh` is still useful as a bootstrap/dev-mode smoke test, but it
currently rewrites first-party packages to dev mode and therefore is not a strict registry-first
proof.

This repo is both the default consumer app and the default local dev-mode host.

If you want to work on packages locally, place the checkout inside this app:

- `packages/dev/core/framework/`
- `packages/dev/core/cms/`
- `packages/dev/themes/<theme-id>/`
- `plugins/dev/<plugin-id>/`

Then point `radaptor.json` to those local `source.path` values for dev mode.

The first-party package paths under `packages/dev/...` are expected to be full nested Git repos.
Normal package PR and release work happens directly inside those nested repos, not through temporary
PR clones. The legacy workspace-level `package-origins/` directory may still exist for local
experiments, but it is not part of the standard workflow.

## Docker CLI options

Use one of these supported approaches:

- Docker Desktop: open a terminal for the running `php` container, run `bash`, then use `composer`
  and `php radaptor.php ...` directly
- `./php-shell.sh`: open a shell in the running `php` container
- `./composer.sh <args>`: run Composer in the `php` container
- `./radaptor <args>`: run the Radaptor CLI in the `php` container
- `./radaptor.sh <args>`: compatibility shim for `./radaptor <args>`

When you use `--json`, progress chatter is suppressed from stdout and appended to
`.logs/cli_commands.log`, with each command separated by its own log section.

Examples:

- `./composer.sh install`
- `./radaptor install --json`
- `./radaptor update --json`
- `docker compose -f docker-compose-dev.yml exec -T -e XDEBUG_MODE=off php phpunit`
- `docker compose -f docker-compose-dev.yml exec -T -e XDEBUG_MODE=off php phpstan analyze`

## Repo baseline

This repo uses tracked Git hooks and a repo-local baseline check.

- `./.githooks/install.sh`: configure `core.hooksPath` to the tracked `.githooks/` directory
- `./bin/check-repo-baseline.sh`: run the same repo baseline and formatting check that GitHub Actions uses

If you clone or recreate this repo locally, install the tracked hooks before relying on pre-commit behavior.

## Browser Event API Docs

The framework ships a public browser-event manual in both JSON and HTML.

- catalog JSON: `http://localhost/?context=events&event=index&format=json`
- catalog HTML: `http://localhost/?context=events&event=index&format=html`
- detail JSON example: `http://localhost/?context=events&event=show&slug=resource:view&format=json`
- detail HTML example: `http://localhost/?context=events&event=show&slug=resource:view&format=html`

If you add or change documented browser events, rebuild the generated registry inside the `php`
container:

- `./radaptor build:event-docs`

## Notes

- The committed manifest is registry-first. Local package development via `source.path` is supported, but it is an explicit opt-in dev mode.
- Package assets are generated under `public/www/assets/packages/` and are git-ignored.
- `framework`, `cms`, and `portal-admin` are expected to come from the registry, not from sibling working copies.
