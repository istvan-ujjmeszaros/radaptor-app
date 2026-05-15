# Radaptor App Skeleton

`radaptor-app-skeleton` is the minimal, registry-first application skeleton for Radaptor. Use it
as the starting point for new consumer apps.

It is intentionally small:
- default packages: `framework`, `cms`, `portal-admin`
- first-run data: one bootstrap admin user; no public homepage (app-owned content is created
  explicitly through the admin UI or `resource-spec:sync`)

For maintainer-local package work, see the editable first-party package repo workflow below.

## Create a new app from this skeleton

### Recommended path — via `radaptor-starter`

```bash
git clone git@github.com:istvan-ujjmeszaros/radaptor-starter.git
cd radaptor-starter
./init.sh my-app
```

`init.sh` clones this skeleton into `./my-app`, removes the upstream `.git` so the consumer
becomes its own codebase, substitutes the `radaptor-app-skeleton` placeholder → `my-app` in
agent docs (`AGENTS.md`, `CLAUDE.md`, `.claude/agents/*.md`), and prints the next setup steps.

### Direct path — clone this repo manually

```bash
git clone git@github.com:istvan-ujjmeszaros/radaptor-app-skeleton.git my-app
cd my-app
rm -rf .git
# replace `radaptor-app-skeleton` → my-app in AGENTS.md, CLAUDE.md, .claude/agents/*.md
git init && git add -A && git commit -m "Initial commit from radaptor-app-skeleton"
```

## First-time setup

Inside the new app directory:

```bash
cp .env.example .env                    # adjust ports and bootstrap admin password if needed
./docker/build-php-platform.sh dev      # build local PHP platform image (first run only)
docker compose -f docker-compose-dev.yml up -d --build
./composer.sh install
./radaptor install --json
```

Then open:

- `http://localhost/login.html` — login (default `admin` / `admin123456`)
- `http://localhost/admin/` — admin shell

Change the bootstrap admin password after the first login. Public pages including `/` are
app-owned content; create them through the admin UI or an explicit `resource-spec:sync`.

All supported CLI work happens inside the `php` container. Host PHP and host Composer are not
part of the supported workflow.

### Default ACL baseline

After install:
- `/login.html` is an explicitly public special page
- `/admin/` is non-inheriting and admin-only
- `/` inherits a private root ACL baseline for logged-in users

Anonymous access to protected pages keeps the requested URL and renders the login page with
HTTP 403 instead of redirecting. Direct access to `/login.html` itself returns 200.

### Bootstrap admin credentials

The first `mandatory` app seed ensures a bootstrap admin user from `.env`:
- `APP_BOOTSTRAP_ADMIN_USERNAME`
- `APP_BOOTSTRAP_ADMIN_PASSWORD`
- `APP_BOOTSTRAP_ADMIN_LOCALE`
- `APP_BOOTSTRAP_ADMIN_TIMEZONE`

## Docker CLI usage

Use one of these supported approaches:

- Docker Desktop: open a terminal for the running `php` container, run `bash`, then use
  `composer` and `php radaptor.php …`
- `./php-shell.sh` — open a shell in the running `php` container
- `./composer.sh <args>` — run Composer in the `php` container
- `./radaptor <args>` — run the Radaptor CLI in the `php` container
- `./radaptor.sh <args>` — compatibility shim for `./radaptor <args>`

When you use `--json`, progress chatter is suppressed from stdout and appended to
`.logs/cli_commands.log`, with each command separated by its own log section.

Common commands:

```bash
./composer.sh install
./radaptor install --json
./radaptor update --json
docker compose -f docker-compose-dev.yml exec -T -e XDEBUG_MODE=off php phpunit
docker compose -f docker-compose-dev.yml exec -T -e XDEBUG_MODE=off php phpstan analyze
```

Do not run PHPUnit, PHPStan, Composer, PHP-CS-Fixer, or the Radaptor CLI through host PHP.

## Local override and parallel instances

To run a second instance against a different registry or set of ports, override defaults in
shell env or `.env`:

```bash
export RADAPTOR_REGISTRY_URL=http://host.docker.internal:8091/registry.json
export COMPOSE_PROJECT_NAME=radaptor-app-skeleton-dev
export APP_HTTP_PORT=8085
export APP_HTTPS_PORT=8445
export APP_DB_PORT=3309
docker compose -f docker-compose-dev.yml up -d --build
```

For a fully parallel playground, clone this skeleton into a different folder and give it its own
compose project name and host ports.

## Local PHP platform image

The PHP 8.5 stack is split into:
- a heavy local platform image built by `./docker/build-php-platform.sh`
- a thin runtime image used by `docker compose`

Default local platform tags:
- `radaptor-app-skeleton-php-platform:8.5-dev-local`
- `radaptor-app-skeleton-php-platform:8.5-prod-local`

Override the runtime base with `RADAPTOR_PHP_PLATFORM_DEV_IMAGE` /
`RADAPTOR_PHP_PLATFORM_PROD_IMAGE` if you need a different prebuilt image.

Build commands:

```bash
./docker/build-php-platform.sh dev    # dev only
./docker/build-php-platform.sh prod   # prod only
./docker/build-php-platform.sh all    # both
```

## What is committed

This skeleton commits:
- `radaptor.json`
- `radaptor.lock.json`

The committed lockfile pins tested package versions. On first run, `radaptor install` uses the
locked `core.framework` package metadata and the configured registry URL to bootstrap the
framework package into `packages/registry/core/framework` before delegating into the framework
CLI.

The default registry URL is `https://packages.radaptor.com/registry.json`. Local development can
override it with `RADAPTOR_REGISTRY_URL` or `.env`. The first-run DB bootstrap relies on the
MariaDB init schema in `docker/mariadb/initdb.d/`.

## Site snapshot export / import

The CMS package provides a full site-content snapshot workflow for disaster recovery when an
SQL backup is unavailable. It exports database-backed content, metadata, widget assignments,
resource trees, menus, ACLs, rich text, i18n rows, and an upload manifest. Physical uploaded
files remain a separate backup step.

```bash
./radaptor site:export --output tmp/site-snapshot.json --uploads-backed-up --json
./radaptor site:uploads-check --snapshot tmp/site-snapshot.json --json
./radaptor site:import tmp/site-snapshot.json --dry-run --json
./radaptor site:import tmp/site-snapshot.json --apply --replace --json
```

The real import is destructive and requires `--apply --replace`. Copy uploaded files into the
target upload directory before applying. After a successful import, Radaptor runs i18n tag
sync, shipped i18n sync, translation-memory rebuild, `build:all` including `build:assets`, and
cache flush.

## Browser event API docs

The framework ships a public browser-event manual in JSON and HTML.

- catalog JSON: `http://localhost/?context=events&event=index&format=json`
- catalog HTML: `http://localhost/?context=events&event=index&format=html`
- detail JSON: `http://localhost/?context=events&event=show&slug=resource:view&format=json`
- detail HTML: `http://localhost/?context=events&event=show&slug=resource:view&format=html`

Rebuild the generated registry after browser-event changes:

```bash
./radaptor build:event-docs
```

## Maintainer notes — first-party package releases

When this skeleton is validated in registry-first mode, first-party package changes must be
released as new immutable versions before the consumer app is updated:

- the committed `radaptor.json` stays registry-first
- maintainer-local first-party overrides live only in gitignored `radaptor.local.json`
- `packages/registry/...` and `packages-dev/...` must never be connected with symlinks
- `install` / `update` may only touch `packages/registry/...`; `packages-dev/...` is Git-owned
- local dev mode does not need release/publish
- registry-first validation does need an immutable package release after first-party changes
- the consumer app refresh is `./radaptor update --ignore-local-overrides --json`, only after
  the registry deploy completed
- after refresh, verify: no symlinks under `packages/registry/`, correct `radaptor.lock.json`
  `resolved.path` values, and a repeated `./radaptor update --json` leaves
  `packages-dev/.../.git` intact

Supported maintainer release commands:

```bash
./radaptor package:release <package-key> --json
./radaptor package:prerelease <package-key> --channel alpha|beta|rc --json
```

After releasing:

1. commit the bumped `.registry-package.json` in the package repo
2. commit + push the `radaptor_package_registry` repo (auto-deploys to packages.radaptor.com)
3. run `./radaptor update --ignore-local-overrides --json` so the consumer picks up the new
   version

The low-level `package:publish` and `package:publish-all` commands remain for internal/bootstrap
cases but refuse to overwrite an already published version.

`./bin/prove-radaptor-app-skeleton-bootstrap.sh` is useful as a bootstrap/dev-mode smoke test
but rewrites first-party packages to dev mode and is therefore not a strict registry-first
proof.

### Editable first-party package repos

Editable first-party repos live under the app root in the gitignored `packages-dev/` directory:

- `packages-dev/core/framework/`
- `packages-dev/core/cms/`
- `packages-dev/themes/<theme-id>/`

Standalone `docker-compose-dev.yml` stays registry-first. For first-party package dev mode,
start the app through the package-dev helper so `/workspace/packages-dev/...` and the
maintainer registry checkout are mounted:

```bash
./bin/docker-compose-packages-dev.sh radaptor-app-skeleton up -d --build
```

To work on packages locally:

1. keep committed `radaptor.json` untouched and registry-first
2. create gitignored `radaptor.local.json`
3. start the package-dev runtime via `./bin/docker-compose-packages-dev.sh radaptor-app-skeleton ...`
4. map first-party package `source.location` values under `core/...` or `themes/...`
5. run app CLI commands that depend on local overrides through the same package-dev runtime:

   ```bash
   ./bin/docker-compose-packages-dev.sh radaptor-app-skeleton exec -T php bash -lc \
     'cd /app && php radaptor.php install --json'
   ./bin/docker-compose-packages-dev.sh radaptor-app-skeleton exec -T php bash -lc \
     'cd /app && php radaptor.php update --json'
   ```

While local overrides are active, the app writes `radaptor.local.lock.json` instead of mutating
the committed lockfile. Use the package-dev runtime after the committed lockfile changes upstream
and you want to reseed local dev state:

```bash
./bin/docker-compose-packages-dev.sh radaptor-app-skeleton exec -T php bash -lc \
  'cd /app && php radaptor.php local-lock:refresh --json'
```

The package-dev runtime makes dev mode explicit:
- `RADAPTOR_WORKSPACE_DEV_MODE=1`
- `RADAPTOR_DEV_ROOT=/workspace/packages-dev`
- `RADAPTOR_PACKAGE_REGISTRY_ROOT=/workspace/radaptor_package_registry`
- only the literal `RADAPTOR_WORKSPACE_DEV_MODE=1` value enables package-dev mode

If `radaptor.local.json` exists without that runtime mode, bootstrap and CLI fail fast instead
of guessing.

`packages/registry/...` is install-owned runtime state, not a source of truth; do not edit it
for first-party package development.

Useful maintainer package commands:

```bash
./bin/docker-compose-packages-dev.sh radaptor-app-skeleton exec -T php bash -lc \
  'cd /app && php radaptor.php package:status --json'
./bin/docker-compose-packages-dev.sh radaptor-app-skeleton exec -T php bash -lc \
  'cd /app && php radaptor.php package:release <package-key> --json'
./bin/docker-compose-packages-dev.sh radaptor-app-skeleton exec -T php bash -lc \
  'cd /app && php radaptor.php package:prerelease <package-key> --channel alpha|beta|rc --json'
```

The first-party package repos under `packages-dev/...` are full nested Git repos. Normal
package PR and release work happens directly inside those repos. The legacy workspace-level
`package-origins/` directory may still exist for local experiments but is not part of the
standard workflow.

### Maintainer note — PR, Codex review, and publish sequence

For package work that must be published:

1. Make and commit the change in the owning package repo under `packages-dev/...`.
2. Push a package PR and comment exactly `@codex review`.
3. Address actionable review threads with thread-aware review reads, then request a fresh
   `@codex review`.
4. Wait for repo checks and the latest Codex review to be clean.
5. Squash-merge the package PR and fast-forward local package `main`.
6. Release from the package-dev runtime:

   ```bash
   ./bin/docker-compose-packages-dev.sh radaptor-app-skeleton exec -T php bash -lc \
     'cd /app && php radaptor.php package:release core:cms --json'
   ```

7. Commit/push the package repo `.registry-package.json` bump.
8. Commit/push `radaptor_package_registry`, then wait for the deploy workflow.
9. Refresh this app in registry-first mode:

   ```bash
   ./radaptor update --ignore-local-overrides --json
   ```

10. If `radaptor.local.json` is active, refresh local dev state afterwards:

    ```bash
    ./bin/docker-compose-packages-dev.sh radaptor-app-skeleton exec -T php bash -lc \
      'cd /app && php radaptor.php local-lock:refresh --json'
    ```

11. Run `build:all` through the package-dev runtime and smoke the affected browser/admin URLs:

    ```bash
    ./bin/docker-compose-packages-dev.sh radaptor-app-skeleton exec -T php bash -lc \
      'cd /app && php radaptor.php build:all'
    ```

## Repo baseline

This repo uses tracked Git hooks and a repo-local baseline check.

- `./.githooks/install.sh` — configure `core.hooksPath` to `.githooks/`
- `./bin/check-repo-baseline.sh` — same baseline check that GitHub Actions runs

If you clone or recreate this repo locally, install the tracked hooks before relying on
pre-commit behavior.

## Notes

- The committed manifest is registry-first. Maintainer-local first-party package development is
  enabled through gitignored `radaptor.local.json`, but only when the package-dev compose override
  is active.
- Package assets are generated under `public/www/assets/packages/` and are git-ignored.
- `framework`, `cms`, `portal-admin`, and `so-admin` are expected to come from the registry in
  committed state; local overrides are maintainer-only runtime state.
