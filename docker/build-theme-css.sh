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

# Create output directory in the extracted theme package.
PORTAL_ADMIN_ROOT="$RADAPTOR_ROOT/packages/dev/themes/portal-admin"
mkdir -p "$PORTAL_ADMIN_ROOT/public/assets/themes/radaptor-portal-admin/css"

echo "Building RadaptorPortalAdmin CSS..."
echo "  App root: $RADAPTOR_ROOT"

npx sass scss/radaptor-portal-admin/main.scss \
     "$PORTAL_ADMIN_ROOT/public/assets/themes/radaptor-portal-admin/css/radaptor-portal-admin.css" \
     --style=compressed \
     --no-source-map \
     --load-path=node_modules \
     --load-path="$RADAPTOR_ROOT/.." \
     --quiet-deps

echo "Done! Output: $PORTAL_ADMIN_ROOT/public/assets/themes/radaptor-portal-admin/css/radaptor-portal-admin.css"
echo "Size: $(wc -c < \"$PORTAL_ADMIN_ROOT/public/assets/themes/radaptor-portal-admin/css/radaptor-portal-admin.css\") bytes"
