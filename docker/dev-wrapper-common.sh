#!/usr/bin/env bash
set -euo pipefail

radaptor_wrapper_project_root() {
	local script_path="$1"
	(cd "$(dirname "$script_path")" && pwd)
}

radaptor_wrapper_home() {
	printf '/tmp/radaptor-cli/%s' "$(id -u)"
}

radaptor_wrapper_read_env_value() {
	local env_file="$1"
	local key="$2"
	local name
	local value

	[ -f "$env_file" ] || return 1

	while IFS='=' read -r name value || [ -n "$name" ]; do
		[[ "$name" != "$key" ]] && continue
		value="${value%$'\r'}"
		value="${value%\"}"
		value="${value#\"}"
		value="${value%\'}"
		value="${value#\'}"
		printf '%s' "$value"
		return 0
	done < "$env_file"

	return 1
}

radaptor_wrapper_env_file() {
	local env_file="${RADAPTOR_DOCKER_ENV_FILE:-.env}"

	if [[ "$env_file" = /* ]]; then
		printf '%s' "$env_file"
	else
		printf '%s/%s' "$(pwd)" "$env_file"
	fi
}

radaptor_wrapper_compose_base_args() {
	local -n _result="$1"
	local env_file
	local compose_files
	local workspace_dev_mode
	local file

	env_file="$(radaptor_wrapper_env_file)"
	_result=()

	if [ -f "$env_file" ]; then
		_result+=(--env-file "$env_file")
	elif [ -n "${RADAPTOR_DOCKER_ENV_FILE:-}" ]; then
		printf 'Configured RADAPTOR_DOCKER_ENV_FILE does not exist: %s\n' "$env_file" >&2
		return 1
	fi

	compose_files="${RADAPTOR_DOCKER_COMPOSE_FILES:-$(radaptor_wrapper_read_env_value "$env_file" RADAPTOR_DOCKER_COMPOSE_FILES || true)}"
	workspace_dev_mode="${RADAPTOR_WORKSPACE_DEV_MODE:-$(radaptor_wrapper_read_env_value "$env_file" RADAPTOR_WORKSPACE_DEV_MODE || true)}"

	if [ -z "$compose_files" ]; then
		compose_files="docker-compose-dev.yml"

		if [ "$workspace_dev_mode" = "1" ]; then
			if [ -f "docker-compose.packages-dev.yml" ]; then
				compose_files="${compose_files}:docker-compose.packages-dev.yml"
			elif [ -f "../docker-compose.packages-dev.yml" ]; then
				compose_files="${compose_files}:../docker-compose.packages-dev.yml"
			fi
		fi
	fi

	IFS=':' read -ra files <<< "$compose_files"

	for file in "${files[@]}"; do
		[ -n "$file" ] || continue
		_result+=(-f "$file")
	done
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
	local compose_args host_uid host_gid wrapper_home
	radaptor_wrapper_compose_base_args compose_args
	host_uid="$(id -u)"
	host_gid="$(id -g)"
	wrapper_home="$(radaptor_wrapper_home)"

	docker compose "${compose_args[@]}" exec -T \
		-e HOST_UID="$host_uid" \
		-e HOST_GID="$host_gid" \
		-e WRAPPER_HOME="$wrapper_home" \
		php bash -lc 'cd /app && ./docker/dev-permissions.sh preflight'
}

radaptor_wrapper_exec_in_php_as_host_user() {
	local compose_args exec_args host_uid host_gid wrapper_home
	radaptor_wrapper_compose_base_args compose_args
	radaptor_wrapper_compose_exec_args exec_args
	host_uid="$(id -u)"
	host_gid="$(id -g)"
	wrapper_home="$(radaptor_wrapper_home)"

	exec docker compose "${compose_args[@]}" "${exec_args[@]}" \
		--user "${host_uid}:${host_gid}" \
		-e HOME="$wrapper_home" \
		-e COMPOSER_HOME="$wrapper_home/.composer" \
		-e XDG_CACHE_HOME="$wrapper_home/.cache" \
		-e COMPOSER_CACHE_DIR="$wrapper_home/.cache/composer" \
		php "$@"
}
