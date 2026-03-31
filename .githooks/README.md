# Git Hooks

This directory contains version-controlled git hooks for the project.

## Installation

To install the git hooks manually, run:

```bash
./.githooks/install.sh
```

You can also run `./scripts/setup-formatting.sh`, which installs PHP-CS-Fixer (via Phive) and links the hooks in one step.

## Requirements

Install PHP-CS-Fixer via [Phive](https://phar.io/):

```bash
phive install --force-accept-unsigned php-cs-fixer
```

The hook expects the binary at `tools/php-cs-fixer`.

## Configuration

The hook **automatically detects** your environment and uses the appropriate method:

### 1. Docker (Auto-detected)

If `docker-compose-dev.yml` is running, the hook automatically runs inside the containerized PHP service. No configuration needed!

```bash
# Just make sure Docker is running
docker compose -f docker-compose-dev.yml up -d
```

### 2. Host PHP (Auto-detected)

If Docker is not running, the hook falls back to the host and runs `tools/php-cs-fixer` directly.

### 3. Custom Docker Service (Optional)

If you have a different service name in `docker-compose-dev.yml`:

```bash
# Add to your ~/.bashrc or ~/.zshrc
export RADAPTOR_DOCKER_PHP_SERVICE="my-php-service"
```

**Note:** `RADAPTOR_DOCKER_PHP_SERVICE` is a project-wide convention. See the main README for details.

## Available Hooks

### pre-commit

Automatically regenerates the autoloader map and runs PHP-CS-Fixer on all staged PHP files before committing. This ensures fast class loading and consistent code formatting across the project.

The hook will:
1. Auto-detect Docker or host PHP environment
2. Run `radaptor build:autoloader`
3. Re-add `generated/__autoload__.php` if it changed
4. Format all staged PHP files according to `.php-cs-fixer.php` configuration
5. Re-add formatted files to the staging area
6. Abort commit if any step fails

**Notes:**
- Runs in Docker by default, but falls back to the host if the container is not up.
- Excludes `/generated/` (auto-generated files) and `/radaptor/radaptor-framework/generators/` (heredoc-heavy templates).

## Manual Formatting

To manually format the entire codebase:

```bash
docker compose -f docker-compose-dev.yml exec -T php bash -c 'tools/php-cs-fixer fix --config=.php-cs-fixer.php'
```

To format specific files:

```bash
tools/php-cs-fixer fix path/to/file.php --config=.php-cs-fixer.php
```
