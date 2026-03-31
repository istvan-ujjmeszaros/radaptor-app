#!/bin/bash
set -euo pipefail

BIN_CANDIDATES=(
	"tools/php-cs-fixer"
	"vendor/bin/php-cs-fixer"
)

for candidate in "${BIN_CANDIDATES[@]}"; do
	if [ -x "$candidate" ]; then
		exec "$candidate" fix "$@"
	fi
done

if command -v php-cs-fixer >/dev/null 2>&1; then
	exec php-cs-fixer fix "$@"
fi

cat <<'ERR' >&2
php-cs-fixer.sh: unable to locate an executable php-cs-fixer binary.
Install it via Phive (see docker/phars.xml), Composer (`composer require --dev friendsofphp/php-cs-fixer`),
or make it available on your PATH, then retry.
ERR
exit 1
