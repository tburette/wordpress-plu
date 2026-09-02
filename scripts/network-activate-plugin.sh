#!/usr/bin/env bash
set -euo pipefail

# Network-activate a plugin so its blocks and patterns are available on
# every site in the development multisite.
# Usage: network-activate-plugin.sh <plugin-slug>

if [ $# -ne 1 ]; then
  printf 'Usage: %s <plugin-slug>\n' "$0" >&2
  exit 1
fi

plugin_slug="$1"

script_dir="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd -P)"
project_dir="$(cd -- "${script_dir}/.." && pwd -P)"

cd -- "${project_dir}"

run_wp() {
  wp-env run cli wp "$@" </dev/null
}

run_wp plugin activate "${plugin_slug}" --network >/dev/null
printf 'Network plugin %s active\n' "${plugin_slug}"
