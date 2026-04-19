#!/usr/bin/env bash
set -euo pipefail

MODE="${1:-}"
APP_ROOT="${APP_ROOT:-/app}"

fail() {
	echo "dev-permissions: $*" >&2
	exit 1
}

require_host_identity() {
	if [ -z "${HOST_UID:-}" ] || [ -z "${HOST_GID:-}" ]; then
		fail "HOST_UID and HOST_GID must be provided."
	fi
}

ensure_directory_state() {
	local path="$1"
	local mode="$2"

	mkdir -p "$path"
	chown "$HOST_UID:$HOST_GID" "$path"
	chmod "$mode" "$path"
}

ensure_file_state() {
	local path="$1"
	local mode="$2"

	mkdir -p "$(dirname "$path")"
	touch "$path"
	chown "$HOST_UID:$HOST_GID" "$path"
	chmod "$mode" "$path"
}

check_owned_path() {
	local path="$1"
	local expected_type="$2"

	if [ ! -e "$path" ]; then
		fail "Expected path is missing: $path"
	fi

	case "$expected_type" in
		dir)
			[ -d "$path" ] || fail "Expected directory but found something else: $path"
			;;
		file)
			[ -f "$path" ] || fail "Expected file but found something else: $path"
			;;
		*)
			fail "Unsupported expected type: $expected_type"
			;;
	esac

	if [ "$(stat -c '%u' "$path")" != "$HOST_UID" ]; then
		fail "Unexpected owner for $path"
	fi

	if [ "$(stat -c '%g' "$path")" != "$HOST_GID" ]; then
		fail "Unexpected group for $path"
	fi
}

preflight() {
	local wrapper_home

	require_host_identity
	wrapper_home="${WRAPPER_HOME:-/tmp/radaptor-cli/$HOST_UID}"

	ensure_directory_state "$wrapper_home" 0775
	ensure_directory_state "$wrapper_home/.composer" 0775
	ensure_directory_state "$wrapper_home/.cache" 0775
	ensure_directory_state "$wrapper_home/.cache/composer" 0775
	ensure_directory_state "$APP_ROOT/.logs" 0775
	ensure_directory_state "$APP_ROOT/generated" 0775
	ensure_file_state "$APP_ROOT/.logs/cli_commands.log" 0664
}

check() {
	local wrapper_home

	require_host_identity
	wrapper_home="${WRAPPER_HOME:-/tmp/radaptor-cli/$HOST_UID}"

	check_owned_path "$APP_ROOT/.logs" dir
	check_owned_path "$APP_ROOT/.logs/cli_commands.log" file
	check_owned_path "$APP_ROOT/generated" dir
	check_owned_path "$wrapper_home" dir
	check_owned_path "$wrapper_home/.composer" dir
	check_owned_path "$wrapper_home/.cache" dir
	check_owned_path "$wrapper_home/.cache/composer" dir

	for path in \
		"$APP_ROOT/packages/dev/core/framework" \
		"$APP_ROOT/packages/dev/core/cms" \
		"$APP_ROOT/packages/dev/themes/portal-admin" \
		"$APP_ROOT/packages/dev/themes/so-admin" \
		"$APP_ROOT/tmp/pr-sync" \
		"$APP_ROOT/_UPLOADS" \
		"$APP_ROOT/vendor"; do
		if [ -e "$path" ]; then
			check_owned_path "$path" dir
		fi
	done

	echo "dev-permissions: check passed"
}

repair() {
	require_host_identity
	preflight

	for path in \
		"$APP_ROOT/packages/dev" \
		"$APP_ROOT/tmp/pr-sync" \
		"$APP_ROOT/_UPLOADS" \
		"$APP_ROOT/vendor"; do
		if [ -e "$path" ]; then
			chown -R "$HOST_UID:$HOST_GID" "$path"
		fi
	done

	echo "dev-permissions: repair completed"
}

case "$MODE" in
	preflight)
		preflight
		;;
	check)
		check
		;;
	repair)
		repair
		;;
	*)
		fail "Usage: ./docker/dev-permissions.sh preflight|check|repair"
		;;
esac
