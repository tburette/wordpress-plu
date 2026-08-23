#!/usr/bin/env bash
set -euo pipefail

# Update .vscode/launch.json to make debugging work correctly
# updates pathmappings to the correct values

project_dir="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd -P)"
project_name="$(basename -- "${project_dir}")"
launch_file="${project_dir}/.vscode/launch.json"
config_file="${project_dir}/.wp-env.json"
cache_root="${HOME}/wp-env"

if [[ ! -f "${launch_file}" ]]; then
  echo "Could not find ${launch_file}." >&2
  exit 1
fi

if [[ ! -f "${config_file}" ]]; then
  echo "Could not find ${config_file}." >&2
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

# The generic /var/www/html mapping only covers the cached WordPress core
# copy. Local themes/plugins declared in .wp-env.json are bind-mounted directly
# to their actual location. They are not copied/symlinked into wordpress_source,
# so each of them needs its own path mapping.
path_mappings="null"

add_mapping() {
  path_mappings="$(jq -cn --argjson base "${path_mappings}" \
    --arg key "${1}" --arg value "${2}" '$base + {($key): $value}')"
}

add_mapping "/var/www/html" "${wordpress_source}"

# map themes
while IFS= read -r rel_path; do
  [[ -z "${rel_path}" ]] && continue
  add_mapping "/var/www/html/wp-content/themes/$(basename -- "${rel_path}")" \
    "${project_dir}/${rel_path}"
done < <(jq -r '.themes[]? | strings | select(startswith("./")) | ltrimstr("./")' "${config_file}")

# map plugins
while IFS= read -r rel_path; do
  [[ -z "${rel_path}" ]] && continue
  add_mapping "/var/www/html/wp-content/plugins/$(basename -- "${rel_path}")" \
    "${project_dir}/${rel_path}"
done < <(jq -r '.plugins[]? | strings | select(startswith("./")) | ltrimstr("./")' "${config_file}")

old_root="$(jq -r '.configurations[] | select(.name == "wp-env listen for XDebug") | .pathMappings["/var/www/html"]' "${launch_file}")"

jq --argjson mappings "${path_mappings}" \
  '(.configurations[] | select(.name == "wp-env listen for XDebug") | .pathMappings) = $mappings' \
  "${launch_file}" > "${launch_file}.tmp"
mv "${launch_file}.tmp" "${launch_file}"

echo "Updated pathMappings in ${launch_file}:"
jq -r --arg old "${old_root}" \
  '.configurations[] | select(.name == "wp-env listen for XDebug")
   | .pathMappings | to_entries[]
   | "  \(.key)\n    was: \(if .key == "/var/www/html" then $old else "(not mapped before)" end)\n    now: \(.value)"' \
  "${launch_file}"
