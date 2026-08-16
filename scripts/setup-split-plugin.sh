#!/usr/bin/env bash
set -euo pipefail

# Network-activate the local split-section prototype so its blocks and
# patterns are available on every site in the development multisite.
script_dir="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd -P)"
project_dir="$(cd -- "${script_dir}/.." && pwd -P)"
plugin_slug="lpu-split-section"

cd -- "${project_dir}"

run_wp() {
  wp-env run cli wp "$@" </dev/null
}

run_wp plugin activate "${plugin_slug}" --network >/dev/null
printf 'Network plugin %s active\n' "${plugin_slug}"
