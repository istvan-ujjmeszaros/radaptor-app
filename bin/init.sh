#!/usr/bin/env bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
ENV_EXAMPLE_PATH="${ROOT_DIR}/.env.example"
ENV_PATH="${ROOT_DIR}/.env"
MANIFEST_PATH="${ROOT_DIR}/radaptor.json"
LOCKFILE_PATH="${ROOT_DIR}/radaptor.lock.json"

DEFAULT_REGISTRY_URL="http://host.docker.internal:8091/registry.json"
PLACEHOLDER_REGISTRY_URL="https://packages.example.invalid/registry.json"

NON_INTERACTIVE=0
REGISTRY_URL=""
COMPOSE_PROJECT_NAME_VALUE=""
APP_HTTP_PORT_VALUE=""
APP_HTTPS_PORT_VALUE=""
APP_DB_PORT_VALUE=""
APP_SWOOLE_PORT_VALUE=""
APP_MAILPIT_HTTP_PORT_VALUE=""
APP_MAILPIT_SMTP_PORT_VALUE=""
APP_BOOTSTRAP_ADMIN_USERNAME_VALUE=""
APP_BOOTSTRAP_ADMIN_PASSWORD_VALUE=""
APP_BOOTSTRAP_ADMIN_LOCALE_VALUE=""
APP_BOOTSTRAP_ADMIN_TIMEZONE_VALUE=""

usage() {
	cat <<'EOF'
Usage: ./bin/init.sh [options]

Options:
  --registry-url URL
  --compose-project-name NAME
  --http-port PORT
  --https-port PORT
  --db-port PORT
  --swoole-port PORT
  --mailpit-http-port PORT
  --mailpit-smtp-port PORT
  --admin-username NAME
  --admin-password PASSWORD
  --admin-locale LOCALE
  --admin-timezone TIMEZONE
  --non-interactive
  --help
EOF
}

require_command() {
	local cmd="$1"

	if ! command -v "${cmd}" >/dev/null 2>&1; then
		echo "Required command not found: ${cmd}" >&2
		exit 1
	fi
}

slugify_project_name() {
	local value="$1"

	value="$(printf '%s' "${value}" | tr '[:upper:]' '[:lower:]')"
	value="$(printf '%s' "${value}" | sed -E 's/[^a-z0-9]+/-/g; s/^-+//; s/-+$//')"

	if [[ -z "${value}" ]]; then
		value="radaptor-app"
	fi

	printf '%s\n' "${value}"
}

read_env_value() {
	local key="$1"

	if [[ ! -f "${ENV_PATH}" ]]; then
		return 0
	fi

	python3 - "${ENV_PATH}" "${key}" <<'PY'
import sys
from pathlib import Path

path = Path(sys.argv[1])
key = sys.argv[2]

for line in path.read_text(encoding="utf-8").splitlines():
    if not line or line.lstrip().startswith("#") or "=" not in line:
        continue
    current_key, value = line.split("=", 1)
    if current_key.strip() == key:
        print(value.strip())
        break
PY
}

write_env_value() {
	local key="$1"
	local value="$2"

	python3 - "${ENV_PATH}" "${key}" "${value}" <<'PY'
import sys
from pathlib import Path

path = Path(sys.argv[1])
key = sys.argv[2]
value = sys.argv[3]

lines = path.read_text(encoding="utf-8").splitlines()
updated = False

for index, line in enumerate(lines):
    if not line or line.lstrip().startswith("#") or "=" not in line:
        continue
    current_key, _ = line.split("=", 1)
    if current_key.strip() == key:
        lines[index] = f"{key}={value}"
        updated = True
        break

if not updated:
    if lines and lines[-1] != "":
        lines.append("")
    lines.append(f"{key}={value}")

path.write_text("\n".join(lines) + "\n", encoding="utf-8")
PY
}

read_manifest_registry_url() {
	python3 - "${MANIFEST_PATH}" <<'PY'
import json
import sys
from pathlib import Path

path = Path(sys.argv[1])
data = json.loads(path.read_text(encoding="utf-8"))
print(data["registries"]["default"]["url"])
PY
}

write_manifest_registry_url() {
	local registry_url="$1"

	python3 - "${MANIFEST_PATH}" "${registry_url}" <<'PY'
import json
import sys
from pathlib import Path

path = Path(sys.argv[1])
registry_url = sys.argv[2]

data = json.loads(path.read_text(encoding="utf-8"))
data["registries"]["default"]["url"] = registry_url
path.write_text(json.dumps(data, indent=2) + "\n", encoding="utf-8")
PY
}

bootstrap_framework_package() {
	local registry_url="$1"

	python3 - "${ROOT_DIR}" "${LOCKFILE_PATH}" "${registry_url}" <<'PY'
import hashlib
import json
import shutil
import sys
import tempfile
import urllib.parse
import urllib.request
import zipfile
from pathlib import Path

root_dir = Path(sys.argv[1])
lockfile_path = Path(sys.argv[2])
registry_url = sys.argv[3]

target_dir = root_dir / "packages" / "registry" / "core" / "framework"
bootstrap_file = target_dir / "bootstrap.php"

if bootstrap_file.is_file():
    print(f"Framework bootstrap already present at {target_dir}")
    sys.exit(0)

if not lockfile_path.is_file():
    raise SystemExit(f"Missing lockfile at {lockfile_path}")

lock = json.loads(lockfile_path.read_text(encoding="utf-8"))
framework = (((lock.get("core") or {}).get("framework")) or {})
package_name = framework.get("package")
resolved = framework.get("resolved") or {}
version = resolved.get("version")

if not isinstance(package_name, str) or not package_name:
    raise SystemExit("Lockfile is missing core.framework.package")

if not isinstance(version, str) or not version:
    raise SystemExit("Lockfile is missing core.framework.resolved.version")

with urllib.request.urlopen(registry_url) as response:
    registry = json.load(response)

package_entry = ((registry.get("packages") or {}).get(package_name) or {})
version_entry = ((package_entry.get("versions") or {}).get(version) or {})
dist = version_entry.get("dist") or {}
dist_url = dist.get("url")
dist_sha256 = dist.get("sha256")

if not isinstance(dist_url, str) or not dist_url:
    raise SystemExit(f"Registry package {package_name} version {version} is missing dist.url")

if not isinstance(dist_sha256, str) or not dist_sha256:
    raise SystemExit(f"Registry package {package_name} version {version} is missing dist.sha256")

resolved_dist_url = urllib.parse.urljoin(registry_url, dist_url)

with tempfile.TemporaryDirectory(prefix="radaptor-bootstrap-framework-") as temp_dir:
    temp_root = Path(temp_dir)
    archive_path = temp_root / "package.zip"
    extract_path = temp_root / "extract"
    extract_path.mkdir(parents=True, exist_ok=True)

    with urllib.request.urlopen(resolved_dist_url) as response, archive_path.open("wb") as target:
        shutil.copyfileobj(response, target)

    actual_sha = hashlib.sha256(archive_path.read_bytes()).hexdigest()

    if actual_sha.lower() != dist_sha256.strip().lower():
        raise SystemExit(
            f"Framework bootstrap archive hash mismatch: expected {dist_sha256}, got {actual_sha}"
        )

    with zipfile.ZipFile(archive_path) as archive:
        archive.extractall(extract_path)

    extracted_entries = [entry for entry in extract_path.iterdir() if entry.name != "__MACOSX"]

    if len(extracted_entries) == 1 and extracted_entries[0].is_dir():
        package_root = extracted_entries[0]
    elif (extract_path / ".registry-package.json").is_file():
        package_root = extract_path
    else:
        raise SystemExit("Unable to determine extracted framework package root")

    if target_dir.exists():
        shutil.rmtree(target_dir)

    target_dir.parent.mkdir(parents=True, exist_ok=True)
    shutil.copytree(package_root, target_dir)

print(f"Bootstrapped framework package into {target_dir}")
PY
}

is_port_available() {
	local port="$1"

	python3 - "${port}" <<'PY'
import socket
import sys

port = int(sys.argv[1])

with socket.socket(socket.AF_INET, socket.SOCK_STREAM) as sock:
    sock.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
    try:
        sock.bind(("127.0.0.1", port))
    except OSError:
        sys.exit(1)
PY
}

pick_available_port() {
	local preferred="$1"
	local candidate="${preferred}"

	while ! is_port_available "${candidate}"; do
		candidate=$((candidate + 1))
	done

	printf '%s\n' "${candidate}"
}

prompt_value() {
	local label="$1"
	local default_value="$2"
	local result=""

	if [[ "${NON_INTERACTIVE}" -eq 1 ]]; then
		printf '%s\n' "${default_value}"
		return
	fi

	read -r -p "${label} [${default_value}]: " result

	if [[ -z "${result}" ]]; then
		result="${default_value}"
	fi

	printf '%s\n' "${result}"
}

prompt_secret() {
	local label="$1"
	local default_value="$2"
	local result=""

	if [[ "${NON_INTERACTIVE}" -eq 1 ]]; then
		printf '%s\n' "${default_value}"
		return
	fi

	printf '%s [%s]: ' "${label}" "${default_value}"
	read -r -s result
	printf '\n'

	if [[ -z "${result}" ]]; then
		result="${default_value}"
	fi

	printf '%s\n' "${result}"
}

confirm_choice() {
	local label="$1"
	local answer=""

	if [[ "${NON_INTERACTIVE}" -eq 1 ]]; then
		return 0
	fi

	read -r -p "${label} [Y/n]: " answer
	answer="$(printf '%s' "${answer}" | tr '[:upper:]' '[:lower:]')"

	[[ -z "${answer}" || "${answer}" == "y" || "${answer}" == "yes" ]]
}

while [[ $# -gt 0 ]]; do
	case "$1" in
		--registry-url)
			REGISTRY_URL="$2"
			shift 2
			;;
		--registry-url=*)
			REGISTRY_URL="${1#*=}"
			shift
			;;
		--compose-project-name)
			COMPOSE_PROJECT_NAME_VALUE="$2"
			shift 2
			;;
		--compose-project-name=*)
			COMPOSE_PROJECT_NAME_VALUE="${1#*=}"
			shift
			;;
		--http-port)
			APP_HTTP_PORT_VALUE="$2"
			shift 2
			;;
		--http-port=*)
			APP_HTTP_PORT_VALUE="${1#*=}"
			shift
			;;
		--https-port)
			APP_HTTPS_PORT_VALUE="$2"
			shift 2
			;;
		--https-port=*)
			APP_HTTPS_PORT_VALUE="${1#*=}"
			shift
			;;
		--db-port)
			APP_DB_PORT_VALUE="$2"
			shift 2
			;;
		--db-port=*)
			APP_DB_PORT_VALUE="${1#*=}"
			shift
			;;
		--swoole-port)
			APP_SWOOLE_PORT_VALUE="$2"
			shift 2
			;;
		--swoole-port=*)
			APP_SWOOLE_PORT_VALUE="${1#*=}"
			shift
			;;
		--mailpit-http-port)
			APP_MAILPIT_HTTP_PORT_VALUE="$2"
			shift 2
			;;
		--mailpit-http-port=*)
			APP_MAILPIT_HTTP_PORT_VALUE="${1#*=}"
			shift
			;;
		--mailpit-smtp-port)
			APP_MAILPIT_SMTP_PORT_VALUE="$2"
			shift 2
			;;
		--mailpit-smtp-port=*)
			APP_MAILPIT_SMTP_PORT_VALUE="${1#*=}"
			shift
			;;
		--admin-username)
			APP_BOOTSTRAP_ADMIN_USERNAME_VALUE="$2"
			shift 2
			;;
		--admin-username=*)
			APP_BOOTSTRAP_ADMIN_USERNAME_VALUE="${1#*=}"
			shift
			;;
		--admin-password)
			APP_BOOTSTRAP_ADMIN_PASSWORD_VALUE="$2"
			shift 2
			;;
		--admin-password=*)
			APP_BOOTSTRAP_ADMIN_PASSWORD_VALUE="${1#*=}"
			shift
			;;
		--admin-locale)
			APP_BOOTSTRAP_ADMIN_LOCALE_VALUE="$2"
			shift 2
			;;
		--admin-locale=*)
			APP_BOOTSTRAP_ADMIN_LOCALE_VALUE="${1#*=}"
			shift
			;;
		--admin-timezone)
			APP_BOOTSTRAP_ADMIN_TIMEZONE_VALUE="$2"
			shift 2
			;;
		--admin-timezone=*)
			APP_BOOTSTRAP_ADMIN_TIMEZONE_VALUE="${1#*=}"
			shift
			;;
		--non-interactive)
			NON_INTERACTIVE=1
			shift
			;;
		--help|-h)
			usage
			exit 0
			;;
		*)
			echo "Unknown option: $1" >&2
			usage >&2
			exit 1
			;;
	esac
done

require_command python3

if [[ ! -f "${ENV_EXAMPLE_PATH}" ]]; then
	echo "Missing .env.example at ${ENV_EXAMPLE_PATH}" >&2
	exit 1
fi

if [[ ! -f "${MANIFEST_PATH}" ]]; then
	echo "Missing radaptor.json at ${MANIFEST_PATH}" >&2
	exit 1
fi

if [[ ! -f "${LOCKFILE_PATH}" ]]; then
	echo "Missing radaptor.lock.json at ${LOCKFILE_PATH}" >&2
	exit 1
fi

if [[ ! -f "${ENV_PATH}" ]]; then
	cp "${ENV_EXAMPLE_PATH}" "${ENV_PATH}"
	echo "Created ${ENV_PATH}"
fi

CURRENT_REGISTRY_URL="$(read_manifest_registry_url)"
CURRENT_PROJECT_NAME="$(read_env_value COMPOSE_PROJECT_NAME)"
CURRENT_HTTP_PORT="$(read_env_value APP_HTTP_PORT)"
CURRENT_HTTPS_PORT="$(read_env_value APP_HTTPS_PORT)"
CURRENT_DB_PORT="$(read_env_value APP_DB_PORT)"
CURRENT_SWOOLE_PORT="$(read_env_value APP_SWOOLE_PORT)"
CURRENT_MAILPIT_HTTP_PORT="$(read_env_value APP_MAILPIT_HTTP_PORT)"
CURRENT_MAILPIT_SMTP_PORT="$(read_env_value APP_MAILPIT_SMTP_PORT)"
CURRENT_ADMIN_USERNAME="$(read_env_value APP_BOOTSTRAP_ADMIN_USERNAME)"
CURRENT_ADMIN_PASSWORD="$(read_env_value APP_BOOTSTRAP_ADMIN_PASSWORD)"
CURRENT_ADMIN_LOCALE="$(read_env_value APP_BOOTSTRAP_ADMIN_LOCALE)"
CURRENT_ADMIN_TIMEZONE="$(read_env_value APP_BOOTSTRAP_ADMIN_TIMEZONE)"

DIR_BASENAME="$(basename "${ROOT_DIR}")"
DEFAULT_PROJECT_NAME="$(slugify_project_name "${DIR_BASENAME}")-dev"

if [[ -z "${COMPOSE_PROJECT_NAME_VALUE}" ]]; then
	COMPOSE_PROJECT_NAME_VALUE="${CURRENT_PROJECT_NAME:-${DEFAULT_PROJECT_NAME}}"
fi

if [[ -z "${REGISTRY_URL}" ]]; then
	if [[ -n "${CURRENT_REGISTRY_URL}" && "${CURRENT_REGISTRY_URL}" != "${PLACEHOLDER_REGISTRY_URL}" ]]; then
		REGISTRY_URL="${CURRENT_REGISTRY_URL}"
	else
		REGISTRY_URL="${DEFAULT_REGISTRY_URL}"
	fi
fi

if [[ -z "${APP_HTTP_PORT_VALUE}" ]]; then
	APP_HTTP_PORT_VALUE="${CURRENT_HTTP_PORT:-$(pick_available_port 8084)}"
fi

if [[ -z "${APP_HTTPS_PORT_VALUE}" ]]; then
	APP_HTTPS_PORT_VALUE="${CURRENT_HTTPS_PORT:-$(pick_available_port 8444)}"
fi

if [[ -z "${APP_DB_PORT_VALUE}" ]]; then
	APP_DB_PORT_VALUE="${CURRENT_DB_PORT:-$(pick_available_port 3308)}"
fi

if [[ -z "${APP_SWOOLE_PORT_VALUE}" ]]; then
	APP_SWOOLE_PORT_VALUE="${CURRENT_SWOOLE_PORT:-$(pick_available_port 9511)}"
fi

if [[ -z "${APP_MAILPIT_HTTP_PORT_VALUE}" ]]; then
	APP_MAILPIT_HTTP_PORT_VALUE="${CURRENT_MAILPIT_HTTP_PORT:-$(pick_available_port 8026)}"
fi

if [[ -z "${APP_MAILPIT_SMTP_PORT_VALUE}" ]]; then
	APP_MAILPIT_SMTP_PORT_VALUE="${CURRENT_MAILPIT_SMTP_PORT:-$(pick_available_port 1026)}"
fi

if [[ -z "${APP_BOOTSTRAP_ADMIN_USERNAME_VALUE}" ]]; then
	APP_BOOTSTRAP_ADMIN_USERNAME_VALUE="${CURRENT_ADMIN_USERNAME:-admin}"
fi

if [[ -z "${APP_BOOTSTRAP_ADMIN_PASSWORD_VALUE}" ]]; then
	APP_BOOTSTRAP_ADMIN_PASSWORD_VALUE="${CURRENT_ADMIN_PASSWORD:-admin123456}"
fi

if [[ -z "${APP_BOOTSTRAP_ADMIN_LOCALE_VALUE}" ]]; then
	APP_BOOTSTRAP_ADMIN_LOCALE_VALUE="${CURRENT_ADMIN_LOCALE:-en_US}"
fi

if [[ -z "${APP_BOOTSTRAP_ADMIN_TIMEZONE_VALUE}" ]]; then
	APP_BOOTSTRAP_ADMIN_TIMEZONE_VALUE="${CURRENT_ADMIN_TIMEZONE:-UTC}"
fi

if [[ "${NON_INTERACTIVE}" -eq 0 ]]; then
	REGISTRY_URL="$(prompt_value "Package registry URL" "${REGISTRY_URL}")"
	APP_BOOTSTRAP_ADMIN_USERNAME_VALUE="$(prompt_value "Bootstrap admin username" "${APP_BOOTSTRAP_ADMIN_USERNAME_VALUE}")"
	APP_BOOTSTRAP_ADMIN_PASSWORD_VALUE="$(prompt_secret "Bootstrap admin password" "${APP_BOOTSTRAP_ADMIN_PASSWORD_VALUE}")"

	echo
	echo "Suggested local Docker settings:"
	echo "  Compose project: ${COMPOSE_PROJECT_NAME_VALUE}"
	echo "  HTTP port:       ${APP_HTTP_PORT_VALUE}"
	echo "  HTTPS port:      ${APP_HTTPS_PORT_VALUE}"
	echo "  DB port:         ${APP_DB_PORT_VALUE}"
	echo "  Swoole port:     ${APP_SWOOLE_PORT_VALUE}"
	echo "  Mailpit HTTP:    ${APP_MAILPIT_HTTP_PORT_VALUE}"
	echo "  Mailpit SMTP:    ${APP_MAILPIT_SMTP_PORT_VALUE}"
	echo

	if ! confirm_choice "Use these local Docker settings?"; then
		COMPOSE_PROJECT_NAME_VALUE="$(prompt_value "Compose project name" "${COMPOSE_PROJECT_NAME_VALUE}")"
		APP_HTTP_PORT_VALUE="$(prompt_value "HTTP port" "${APP_HTTP_PORT_VALUE}")"
		APP_HTTPS_PORT_VALUE="$(prompt_value "HTTPS port" "${APP_HTTPS_PORT_VALUE}")"
		APP_DB_PORT_VALUE="$(prompt_value "DB port" "${APP_DB_PORT_VALUE}")"
		APP_SWOOLE_PORT_VALUE="$(prompt_value "Swoole port" "${APP_SWOOLE_PORT_VALUE}")"
		APP_MAILPIT_HTTP_PORT_VALUE="$(prompt_value "Mailpit HTTP port" "${APP_MAILPIT_HTTP_PORT_VALUE}")"
		APP_MAILPIT_SMTP_PORT_VALUE="$(prompt_value "Mailpit SMTP port" "${APP_MAILPIT_SMTP_PORT_VALUE}")"
	fi
fi

if [[ -z "${REGISTRY_URL}" || "${REGISTRY_URL}" == "${PLACEHOLDER_REGISTRY_URL}" ]]; then
	echo "A real registry URL is required. Refusing to keep the placeholder URL." >&2
	exit 1
fi

write_env_value COMPOSE_PROJECT_NAME "${COMPOSE_PROJECT_NAME_VALUE}"
write_env_value APP_HTTP_PORT "${APP_HTTP_PORT_VALUE}"
write_env_value APP_HTTPS_PORT "${APP_HTTPS_PORT_VALUE}"
write_env_value APP_DB_PORT "${APP_DB_PORT_VALUE}"
write_env_value APP_SWOOLE_PORT "${APP_SWOOLE_PORT_VALUE}"
write_env_value APP_MAILPIT_HTTP_PORT "${APP_MAILPIT_HTTP_PORT_VALUE}"
write_env_value APP_MAILPIT_SMTP_PORT "${APP_MAILPIT_SMTP_PORT_VALUE}"
write_env_value APP_BOOTSTRAP_ADMIN_USERNAME "${APP_BOOTSTRAP_ADMIN_USERNAME_VALUE}"
write_env_value APP_BOOTSTRAP_ADMIN_PASSWORD "${APP_BOOTSTRAP_ADMIN_PASSWORD_VALUE}"
write_env_value APP_BOOTSTRAP_ADMIN_LOCALE "${APP_BOOTSTRAP_ADMIN_LOCALE_VALUE}"
write_env_value APP_BOOTSTRAP_ADMIN_TIMEZONE "${APP_BOOTSTRAP_ADMIN_TIMEZONE_VALUE}"
write_manifest_registry_url "${REGISTRY_URL}"
bootstrap_framework_package "${REGISTRY_URL}"

cat <<EOF

Initialization complete.

Registry URL:        ${REGISTRY_URL}
Compose project:     ${COMPOSE_PROJECT_NAME_VALUE}
HTTP port:           ${APP_HTTP_PORT_VALUE}
HTTPS port:          ${APP_HTTPS_PORT_VALUE}
DB port:             ${APP_DB_PORT_VALUE}
Swoole port:         ${APP_SWOOLE_PORT_VALUE}
Mailpit HTTP port:   ${APP_MAILPIT_HTTP_PORT_VALUE}
Mailpit SMTP port:   ${APP_MAILPIT_SMTP_PORT_VALUE}
Bootstrap admin:     ${APP_BOOTSTRAP_ADMIN_USERNAME_VALUE}

Next steps:
  docker compose -f docker-compose-dev.yml up -d --build
  docker compose -f docker-compose-dev.yml exec -T php composer install
  docker compose -f docker-compose-dev.yml exec -T php php radaptor.php install --json
EOF
