#!/usr/bin/env bash
set -euo pipefail

radaptor_wrapper_project_root() {
	local script_path="$1"
	(cd "$(dirname "$script_path")" && pwd)
}

radaptor_wrapper_home() {
	printf '/tmp/radaptor-cli/%s' "$(id -u)"
}

radaptor_wrapper_compose_exec_args() {
	local -n _result="$1"
	_result=(exec)

	if [[ ! -t 0 || ! -t 1 ]]; then
		_result+=(-T)
	fi
}

radaptor_wrapper_should_print_info() {
	if [[ ! -t 1 ]]; then
		return 1
	fi

	for arg in "$@"; do
		if [[ "$arg" == "--json" ]]; then
			return 1
		fi
	done

	return 0
}

radaptor_wrapper_run_preflight() {
	local host_uid host_gid wrapper_home
	host_uid="$(id -u)"
	host_gid="$(id -g)"
	wrapper_home="$(radaptor_wrapper_home)"

	docker compose -f docker-compose-dev.yml exec -T \
		-e HOST_UID="$host_uid" \
		-e HOST_GID="$host_gid" \
		-e WRAPPER_HOME="$wrapper_home" \
		php sh -lc '
			mkdir -p "$WRAPPER_HOME/.composer" "$WRAPPER_HOME/.cache/composer" /app/.logs /app/generated &&
			touch /app/.logs/cli_commands.log &&
			chown -R "$HOST_UID:$HOST_GID" "$WRAPPER_HOME" /app/.logs /app/generated 2>/dev/null || true &&
			chmod 0775 "$WRAPPER_HOME" "$WRAPPER_HOME/.composer" "$WRAPPER_HOME/.cache" "$WRAPPER_HOME/.cache/composer" /app/.logs /app/generated 2>/dev/null || true &&
			chmod 0664 /app/.logs/cli_commands.log 2>/dev/null || true
		'
}

radaptor_wrapper_exec_in_php_as_host_user() {
	local exec_args host_uid host_gid wrapper_home
	radaptor_wrapper_compose_exec_args exec_args
	host_uid="$(id -u)"
	host_gid="$(id -g)"
	wrapper_home="$(radaptor_wrapper_home)"

	exec docker compose -f docker-compose-dev.yml "${exec_args[@]}" \
		--user "${host_uid}:${host_gid}" \
		-e HOME="$wrapper_home" \
		-e COMPOSER_HOME="$wrapper_home/.composer" \
		-e XDG_CACHE_HOME="$wrapper_home/.cache" \
		-e COMPOSER_CACHE_DIR="$wrapper_home/.cache/composer" \
		php "$@"
}
