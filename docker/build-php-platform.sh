#!/usr/bin/env bash

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
DOCKERFILE="${SCRIPT_DIR}/php8.5/Dockerfile-platform"
IMAGE_PREFIX="${RADAPTOR_PHP_PLATFORM_IMAGE_PREFIX:-radaptor-app-php-platform}"
PHP_VERSION="${RADAPTOR_PHP_PLATFORM_PHP_VERSION:-8.5}"

usage() {
cat <<EOF
Usage: $0 [dev|prod|all] [docker build args...]

Builds the local PHP platform image used by the thin runtime Dockerfile.

Examples:
  $0 dev
  $0 prod --no-cache
  $0 all --build-arg XDEBUG_COMMIT=master
EOF
}

MODE="${1:-all}"
if [[ $# -gt 0 ]]; then
  shift
fi

case "${MODE}" in
  dev)
    TARGETS=(dev)
    ;;
  prod)
    TARGETS=(prod)
    ;;
  all)
    TARGETS=(dev prod)
    ;;
  -h|--help)
    usage
    exit 0
    ;;
  *)
    echo "Unknown mode: ${MODE}" >&2
    usage >&2
    exit 1
    ;;
esac

for TARGET in "${TARGETS[@]}"; do
  TAG="${IMAGE_PREFIX}:${PHP_VERSION}-${TARGET}-local"

  echo "-------------------------------------------"
  echo "Building PHP platform image ${TAG}"
  echo "-------------------------------------------"

  docker build \
    --build-arg PHP_VERSION="${PHP_VERSION}" \
    --build-arg ENVIRONMENT="${TARGET}" \
    "$@" \
    --target "${TARGET}" \
    -f "${DOCKERFILE}" \
    -t "${TAG}" \
    "${SCRIPT_DIR}"
done
