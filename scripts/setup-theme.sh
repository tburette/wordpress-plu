#!/usr/bin/env bash
set -euo pipefail

# Enable the theme for the multisite network, then activate it on every sites. 
# wp-env mounts a theme but does not set it as active, this script activates the
# theme on all sites.
script_dir="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd -P)"
project_dir="$(cd -- "${script_dir}/.." && pwd -P)"
theme_slug="lepaysanurbain"

cd -- "${project_dir}"

run_wp() {
  wp-env run cli wp "$@" </dev/null
}

# This fails early with WP-CLI's useful error if the mounted theme is missing.
run_wp theme enable "${theme_slug}" --network >/dev/null

site_urls="$(run_wp site list --network=1 --field=url | tr -d '\r')"
if [[ -z "${site_urls}" ]]; then
  printf 'No WordPress sites found; cannot activate theme %s.\n' "${theme_slug}" >&2
  exit 1
fi

while IFS= read -r site_url; do
  [[ -z "${site_url}" ]] && continue
  run_wp theme activate "${theme_slug}" --url="${site_url}" >/dev/null
  printf '%s: theme %s active\n' "${site_url}" "${theme_slug}"
done <<< "${site_urls}"
