#!/usr/bin/env bash
set -euo pipefail

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$PROJECT_ROOT"

if [[ "$#" -eq 0 ]]; then
	exec docker compose -f docker-compose-dev.yml exec php bash
fi

exec_args=(exec)
if [[ ! -t 0 || ! -t 1 ]]; then
	exec_args+=(-T)
fi

exec docker compose -f docker-compose-dev.yml "${exec_args[@]}" php bash -lc "$*"
