#!/usr/bin/env bash
set -euo pipefail

# Create or select one site-local wp_navigation post for each row in
# navigation-sites.tsv. The block markup is loaded from the HTML files in
# this directory, never assembled inside this shell script. The resulting post
# ID is stored in the lpu_navigation_id option so the shared theme header can
# bind the correct navigation on each multisite blog. Existing navigation
# content is preserved.
script_dir="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd -P)"
project_dir="$(cd -- "${script_dir}/../../.." && pwd -P)"
navigation_sites_file="${script_dir}/navigation-sites.tsv"

if [[ ! -f "${navigation_sites_file}" ]]; then
  printf 'Missing navigation data file: %s\n' "${navigation_sites_file}" >&2
  exit 1
fi

cd -- "${project_dir}"

run_wp() {
  # Keep WP-CLI from consuming the TSV currently driving the loop below.
  wp-env run cli wp "$@" </dev/null
}

create_or_select_menu() {
  local site_url="$1"
  local title="$2"
  local content_file="$3"
  local menu_id
  local content_path="${script_dir}/${content_file}"

  if [[ ! -f "${content_path}" ]]; then
    printf 'Missing navigation content file: %s\n' "${content_path}" >&2
    exit 1
  fi

  local menu_content
  menu_content="$(<"${content_path}")"

  menu_id="$(run_wp post list \
    --url="${site_url}" \
    --post_type=wp_navigation \
    --name=menu-principal \
    --posts_per_page=1 \
    --field=ID | tr -d '\r')"

  if [[ -z "${menu_id}" ]]; then
    menu_id="$(run_wp post create \
      --url="${site_url}" \
      --post_type=wp_navigation \
      --post_status=publish \
      --post_title="${title}" \
      --post_name=menu-principal \
      --post_content="${menu_content}" \
      --porcelain | tr -d '\r')"
  fi

  run_wp option update lpu_navigation_id "${menu_id}" --url="${site_url}" >/dev/null
  printf '%s: navigation %s (data: %s)\n' "${site_url}" "${menu_id}" "${content_path}"
}

while IFS='|' read -r site_url navigation_title content_file; do
  [[ -z "${site_url}" || "${site_url}" == \#* ]] && continue

  if [[ -z "${navigation_title}" || -z "${content_file}" ]]; then
    printf 'Invalid navigation data row for %s. Expected site_url|title|content_file.\n' "${site_url}" >&2
    exit 1
  fi

  create_or_select_menu "${site_url}" "${navigation_title}" "${content_file}"
done < "${navigation_sites_file}"

