# Radaptor App

`radaptor-app` is the minimal, registry-first application skeleton for Radaptor.

It is intentionally small:
- default packages: `framework`, `cms`, `portal-admin`
- default plugins: none
- first-run data: one bootstrap admin user and a placeholder homepage

## Quick start

1. Copy `.env.example` to `.env`.
2. Edit `radaptor.json` and set the real package registry URL.
   - The committed manifest intentionally uses a placeholder URL.
   - If the registry is reachable from inside Docker via the host machine, use something like `http://host.docker.internal:8091/registry.json`.
   - Do not use `http://localhost:...` unless the registry actually runs inside the `php` container.
3. Start the local stack:
   - `docker compose -f docker-compose-dev.yml up -d --build`
4. Install PHP dependencies:
   - `docker compose -f docker-compose-dev.yml exec -T php composer install`
5. Install packages and bootstrap the app:
   - `docker compose -f docker-compose-dev.yml exec -T php php radaptor.php install --json`
6. Open the site:
   - homepage: `http://localhost:8084/`
   - login: `http://localhost:8084/login.html`
   - admin: `http://localhost:8084/admin/index.html`

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
- the local fallback `radaptor/radaptor-framework` and `radaptor/radaptor-cms` trees

Those fallback trees exist so the first `radaptor install` can run before any packages are downloaded. After install, bootstrap delegates to the registry-installed package paths from `radaptor.lock.json`.
The committed lockfile pins tested package versions, but the first `radaptor install` still re-resolves them against the registry URL you configured in `radaptor.json`.

## Development commands

- `make up`
- `make install`
- `make test`
- `make phpstan`

## Notes

- The committed manifest is registry-first. Local package development via `source.path` is not the default shape of this skeleton.
- Package assets are generated under `public/www/assets/packages/` and are git-ignored.
- `framework`, `cms`, and `portal-admin` are expected to come from the registry, not from sibling working copies.
