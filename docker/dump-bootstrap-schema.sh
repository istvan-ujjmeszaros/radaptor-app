#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR=$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)
COMPOSE_FILE="$ROOT_DIR/docker-compose-dev.yml"
OUTPUT_FILE="${1:-$ROOT_DIR/docker/mariadb/initdb.d/010-bootstrap-schema.sql}"
DB_NAME="${DB_NAME:-radaptor_app}"
TEST_DB="${TEST_DB:-${DB_NAME}_test}"
MARIADB_ROOT_PASSWORD="${MARIADB_ROOT_PASSWORD:-radaptor_app}"

sanitize_dump() {
	sed \
		-e '/^\/\*M!999999\\- enable the sandbox mode \*\/ *$/d' \
		-e '/^\/\*!40101 SET @OLD_CHARACTER_SET_CLIENT=/d' \
		-e '/^\/\*!40101 SET @OLD_CHARACTER_SET_RESULTS=/d' \
		-e '/^\/\*!40101 SET @OLD_COLLATION_CONNECTION=/d' \
		-e '/^\/\*!40101 SET NAMES /d' \
		-e '/^\/\*!40103 SET @OLD_TIME_ZONE=/d' \
		-e '/^\/\*!40103 SET TIME_ZONE=/d' \
		-e '/^\/\*!40014 SET @OLD_UNIQUE_CHECKS=/d' \
		-e '/^\/\*!40014 SET @OLD_FOREIGN_KEY_CHECKS=/d' \
		-e '/^\/\*!40101 SET @OLD_SQL_MODE=/d' \
		-e '/^\/\*M!100616 SET @OLD_NOTE_VERBOSITY=/d' \
		-e '/^\/\*!40103 SET TIME_ZONE=@OLD_TIME_ZONE/d' \
		-e '/^\/\*!40101 SET SQL_MODE=@OLD_SQL_MODE/d' \
		-e '/^\/\*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS/d' \
		-e '/^\/\*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS/d' \
		-e '/^\/\*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT/d' \
		-e '/^\/\*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS/d' \
		-e '/^\/\*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION/d' \
		-e '/^\/\*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY/d' \
		-e '/^USE `/d' \
		-e 's/ AUTO_INCREMENT=[0-9][0-9]*//g'
}

dump_schema() {
	local db_name="$1"

	printf 'USE `%s`;\n\n' "$db_name"
	docker compose -f "$COMPOSE_FILE" exec -T \
		-e DB_NAME="$db_name" \
		-e ROOT_PASSWORD="$MARIADB_ROOT_PASSWORD" \
		mariadb \
		sh -lc 'mariadb-dump -uroot -p"$ROOT_PASSWORD" --no-data --skip-comments --skip-add-drop-table --skip-add-locks --skip-lock-tables "$DB_NAME"' |
		sanitize_dump
	printf '\n'
}

TMP_FILE=$(mktemp)
trap 'rm -f "$TMP_FILE"' EXIT

{
	echo 'SET FOREIGN_KEY_CHECKS=0;'
	printf '\n'
	dump_schema "$DB_NAME"
	dump_schema "$TEST_DB"
	echo 'SET FOREIGN_KEY_CHECKS=1;'
} > "$TMP_FILE"

mv "$TMP_FILE" "$OUTPUT_FILE"
printf 'Wrote %s\n' "$OUTPUT_FILE"
