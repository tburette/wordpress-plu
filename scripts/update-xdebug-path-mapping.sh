#!/usr/bin/env bash
set -euo pipefail

project_dir="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd -P)"
project_name="$(basename -- "${project_dir}")"
launch_file="${project_dir}/.vscode/launch.json"
cache_root="${HOME}/wp-env"

if [[ ! -f "${launch_file}" ]]; then
  echo "Could not find ${launch_file}." >&2
  exit 1
fi

# Newest wp-env cache instance for this project, e.g.
# $HOME/wp-env/wp-env-<project>-<hash>/WordPress
wordpress_source="$(ls -dt "${cache_root}"/wp-env-"${project_name}"-*/WordPress 2>/dev/null | head -n1 || true)"

if [[ -z "${wordpress_source}" ]]; then
  echo "Could not find the wp-env WordPress source for '${project_name}'." >&2
  echo "Expected: ${cache_root}/wp-env-${project_name}-<hash>/WordPress" >&2
  echo "Start the environment first: npm run env:start:xdebug" >&2
  exit 1
fi

wordpress_source="$(cd -- "${wordpress_source}" && pwd -P)"

old_source="$(jq -r '.configurations[] | select(.name == "wp-env listen for XDebug") | .pathMappings["/var/www/html"]' "${launch_file}")"
jq --arg src "${wordpress_source}" \
  '(.configurations[] | select(.name == "wp-env listen for XDebug") | .pathMappings["/var/www/html"]) = $src' \
  "${launch_file}" > "${launch_file}.tmp"
mv "${launch_file}.tmp" "${launch_file}"

echo "Replaced /var/www/html mapping in ${launch_file}:"
echo "  ${old_source}"
echo "  ->"
echo "  ${wordpress_source}"
