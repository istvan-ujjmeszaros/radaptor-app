# Radaptor App

`radaptor-app` is the minimal, registry-first application skeleton for Radaptor.

It is intentionally small:
- default packages: `framework`, `cms`, `portal-admin`
- default plugins: none
- first-run data: one bootstrap admin user and a placeholder homepage

## Quick start

1. Point the app at a real package registry:
   - `export RADAPTOR_REGISTRY_URL=https://your-registry.example/registry.json`
2. Start the local stack:
   - `docker compose -f docker-compose-dev.yml up -d --build`
3. Run CLI commands in the `php` container:
   - Docker Desktop path: open a terminal for the `php` container and run `bash`
   - shell shortcut: `./php-shell.sh`
   - direct shortcuts:
     - `./composer.sh install`
     - `./radaptor.sh install --json`
4. Open the site:
   - homepage: `http://localhost/`
   - login: `http://localhost/login.html`
   - admin: `http://localhost/admin/index.html`

If you are running multiple local stacks in parallel, use `.env` to override ports and the compose
project name before `docker compose up`.

All supported CLI work happens inside Docker. Host PHP and host Composer are not part of the
supported workflow.

Default ACL baseline after install:
- `/login.html` is an explicitly public special page
- `/admin/` is explicitly non-inheriting and admin-only
- `/` inherits a private root ACL baseline for logged-in users

Anonymous access to protected pages keeps the requested URL and renders the login page with `403`,
instead of redirecting to a different URL. Direct access to `/login.html` itself returns `200`.

What happens on first install:
- `docker compose up` gives you the supported PHP runtime
- `./radaptor.sh install --json` bootstraps the pinned framework package if it is still missing
- then the framework CLI continues the normal install/update/build/migrate/seed flow
- the committed `radaptor.json` stays template-neutral with a placeholder registry URL, so the
  real registry must come from `RADAPTOR_REGISTRY_URL` or a local `.env` override

### Local override example

For a local registry and a second app instance, use shell env or `.env` overrides:

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

The committed lockfile is intentionally template-neutral, so the first install still needs a real
registry URL from `RADAPTOR_REGISTRY_URL` or a local `.env`.
The first-run DB bootstrap currently relies on the MariaDB init schema shipped in `docker/mariadb/initdb.d/`.

This repo is both the default consumer app and the default local dev-mode host.

If you want to work on packages locally, place the checkout inside this app:

- `packages/dev/core/framework/`
- `packages/dev/core/cms/`
- `packages/dev/themes/<theme-id>/`
- `plugins/dev/<plugin-id>/`

Then point `radaptor.json` to those local `source.path` values for dev mode.

## Docker CLI options

Use one of these supported approaches:

- Docker Desktop: open a terminal for the running `php` container, run `bash`, then use `composer`
  and `php radaptor.php ...` directly
- `./php-shell.sh`: open a shell in the running `php` container
- `./composer.sh <args>`: run Composer in the `php` container
- `./radaptor.sh <args>`: run `php radaptor.php ...` in the `php` container

Examples:

- `./composer.sh install`
- `./radaptor.sh install --json`
- `./radaptor.sh update --json`
- `docker compose -f docker-compose-dev.yml exec -T -e XDEBUG_MODE=off php phpunit`
- `docker compose -f docker-compose-dev.yml exec -T -e XDEBUG_MODE=off php phpstan analyze`

## Browser Event API Docs

The framework ships a public browser-event manual in both JSON and HTML.

- catalog JSON: `http://localhost/?context=events&event=index&format=json`
- catalog HTML: `http://localhost/?context=events&event=index&format=html`
- detail JSON example: `http://localhost/?context=events&event=show&slug=resource:view&format=json`
- detail HTML example: `http://localhost/?context=events&event=show&slug=resource:view&format=html`

If you add or change documented browser events, rebuild the generated registry inside the `php`
container:

- `./radaptor.sh build:event-docs`

## Notes

- The committed manifest is registry-first. Local package development via `source.path` is supported, but it is an explicit opt-in dev mode.
- Package assets are generated under `public/www/assets/packages/` and are git-ignored.
- `framework`, `cms`, and `portal-admin` are expected to come from the registry, not from sibling working copies.
