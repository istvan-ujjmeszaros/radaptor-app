#!/bin/bash
#
# Build CSS for RadaptorPortalAdmin theme
#
# This script compiles SCSS using Dart Sass via npx.
# Requires: Node.js and npm packages (sass, bootstrap)
#
# Usage (from radaptor directory):
#   ./docker/build-theme-css.sh
#
# Prerequisites:
#   npm install --no-save sass bootstrap@5.3.3
#

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
RADAPTOR_ROOT="$(dirname "$SCRIPT_DIR")"

cd "$RADAPTOR_ROOT"

# Check for required npm packages
if [ ! -d "node_modules/sass" ] || [ ! -d "node_modules/bootstrap" ]; then
    echo "Installing required npm packages..."
    npm install --no-save sass bootstrap@5.3.3
fi

PORTAL_ADMIN_ROOT=""

for CANDIDATE in \
	"$RADAPTOR_ROOT/packages/dev/themes/portal-admin" \
	"$RADAPTOR_ROOT/packages/registry/themes/portal-admin"
do
	if [ -f "$CANDIDATE/scss/radaptor-portal-admin/main.scss" ]; then
		PORTAL_ADMIN_ROOT="$CANDIDATE"
		break
	fi
done

if [ -z "$PORTAL_ADMIN_ROOT" ]; then
	echo "Unable to locate portal-admin theme SCSS entrypoint."
	echo "Expected one of:"
	echo "  - packages/dev/themes/portal-admin/scss/radaptor-portal-admin/main.scss"
	echo "  - packages/registry/themes/portal-admin/scss/radaptor-portal-admin/main.scss"
	exit 1
fi

CSS_OUTPUT_PATH="$PORTAL_ADMIN_ROOT/public/assets/themes/radaptor-portal-admin/css/radaptor-portal-admin.css"
THEMES_ROOT="$(dirname "$PORTAL_ADMIN_ROOT")"
SHARED_SCSS_TARGET="$THEMES_ROOT/shared-scss"
SHARED_SCSS_SOURCE=""

for CANDIDATE in \
	"$RADAPTOR_ROOT/shared-scss" \
	"$RADAPTOR_ROOT/../shared-scss"
do
	if [ -f "$CANDIDATE/_variables.scss" ]; then
		SHARED_SCSS_SOURCE="$CANDIDATE"
		break
	fi
done

if [ -n "$SHARED_SCSS_SOURCE" ] && [ ! -e "$SHARED_SCSS_TARGET" ]; then
	ln -s "$SHARED_SCSS_SOURCE" "$SHARED_SCSS_TARGET"
fi

mkdir -p "$(dirname "$CSS_OUTPUT_PATH")"

echo "Building RadaptorPortalAdmin CSS..."
echo "  App root: $RADAPTOR_ROOT"

npx sass "$PORTAL_ADMIN_ROOT/scss/radaptor-portal-admin/main.scss" \
     "$CSS_OUTPUT_PATH" \
     --style=compressed \
     --no-source-map \
     --load-path=node_modules \
     --load-path="$RADAPTOR_ROOT/.." \
     --quiet-deps

echo "Done! Output: $CSS_OUTPUT_PATH"
echo "Size: $(wc -c < "$CSS_OUTPUT_PATH") bytes"
