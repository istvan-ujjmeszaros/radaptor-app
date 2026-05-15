#!/usr/bin/env bash
set -euo pipefail

PROJECT_NAME="${1:-}"

if [[ -z "$PROJECT_NAME" ]]; then
	echo "Usage: $0 <compose-project-name> <docker compose args...>" >&2
	exit 2
fi

shift

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
mkdir -p "$PROJECT_ROOT/packages-dev"

export COMPOSE_PROJECT_NAME="$PROJECT_NAME"
export RADAPTOR_DOCKER_VOLUME_PREFIX="${RADAPTOR_DOCKER_VOLUME_PREFIX:-$PROJECT_NAME}"

cd "$PROJECT_ROOT"
exec docker compose \
	-f docker-compose-dev.yml \
	-f docker-compose.packages-dev.yml \
	"$@"
