# Radaptor App

`radaptor-app` is the minimal, registry-first application skeleton for Radaptor.

It is intentionally small:
- default packages: `framework`, `cms`, `portal-admin`
- default plugins: none
- first-run data: one bootstrap admin user and a placeholder homepage

## Quick start

1. Initialize the app:
   - `./bin/init.sh`
   - or `make init`
2. Start the local stack:
   - `docker compose -f docker-compose-dev.yml up -d --build`
3. Install PHP dependencies:
   - `docker compose -f docker-compose-dev.yml exec -T php composer install`
4. Install packages and bootstrap the app:
   - `docker compose -f docker-compose-dev.yml exec -T php php radaptor.php install --json`
5. Open the site:
   - homepage: `http://localhost:8084/`
   - login: `http://localhost:8084/login.html`
   - admin: `http://localhost:8084/admin/index.html`

Default ACL baseline after install:
- `/login.html` is the special login resource rendered by the protected-page fallback
- `/admin/` is explicitly non-inheriting and admin-only
- `/` inherits a private root ACL baseline for logged-in users

Anonymous access to protected pages keeps the requested URL and renders the login page with `403`,
instead of redirecting to a different URL.

The init step:
- creates `.env` from `.env.example` if needed
- sets an isolated Docker Compose project name
- picks non-conflicting host ports if the defaults are already taken
- updates the registry URL inside `radaptor.json`
- sets the bootstrap admin credentials used by the first mandatory seed

### Non-interactive init

You can script the bootstrap flow:

```bash
./bin/init.sh \
  --non-interactive \
  --registry-url http://host.docker.internal:8091/registry.json \
  --compose-project-name radaptor-app-playground-dev \
  --http-port 8085 \
  --https-port 8445 \
  --db-port 3309 \
  --swoole-port 9512 \
  --mailpit-http-port 8027 \
  --mailpit-smtp-port 1027 \
  --admin-username admin \
  --admin-password admin123456
```

### Parallel clone / playground example

If you want to validate a second copy without stopping an existing app instance, use a different
folder and let `init` assign a different compose project and host ports. A typical local playground
location is `tmp/radaptor-app-playground/`.

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
The first-run DB bootstrap currently relies on the MariaDB init schema shipped in `docker/mariadb/initdb.d/`.

## Development commands

- `make init`
- `make up`
- `make composer-install`
- `make install`
- `make update`
- `make test`
- `make phpstan`

## Notes

- The committed manifest is registry-first. Local package development via `source.path` is not the default shape of this skeleton.
- Package assets are generated under `public/www/assets/packages/` and are git-ignored.
- `framework`, `cms`, and `portal-admin` are expected to come from the registry, not from sibling working copies.
