#!/usr/bin/env bash
set -euo pipefail

# Create or select one site-local footer Navigation post for each row in
# footer-sites.tsv. The fragments provide the three visual footer columns and
# their secondary legal-information group; the shared visual frame lives in
# themes/lepaysanurbain/parts/footer.html. Existing footer content is preserved.
#
# The network and farm menus are site-local and are linked to the shared theme
# through the lpu_footer_navigation_id option. Destinations for pages that do
# not exist yet remain provisional until their editorial URLs are validated.
script_dir="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd -P)"
project_dir="$(cd -- "${script_dir}/../../.." && pwd -P)"
footer_sites_file="${script_dir}/footer-sites.tsv"

if [[ ! -f "${footer_sites_file}" ]]; then
  printf 'Missing footer navigation data file: %s\n' "${footer_sites_file}" >&2
  exit 1
fi

cd -- "${project_dir}"

run_wp() {
  wp-env run cli wp "$@" </dev/null
}

create_or_select_footer() {
  local site_url="$1"
  local title="$2"
  local content_file="$3"
  local footer_id
  local content_path="${script_dir}/${content_file}"

  if [[ ! -f "${content_path}" ]]; then
    printf 'Missing footer navigation content file: %s\n' "${content_path}" >&2
    exit 1
  fi

  local footer_content
  footer_content="$(<"${content_path}")"

  footer_id="$(run_wp post list \
    --url="${site_url}" \
    --post_type=wp_navigation \
    --post_status=any \
    --name=footer-principal \
    --posts_per_page=1 \
    --field=ID | tr -d '\r')"

  if [[ -z "${footer_id}" ]]; then
    footer_id="$(run_wp post create \
      --url="${site_url}" \
      --post_type=wp_navigation \
      --post_status=publish \
      --post_title="${title}" \
      --post_name=footer-principal \
      --post_content="${footer_content}" \
      --porcelain | tr -d '\r')"
  fi

  run_wp option update lpu_footer_navigation_id "${footer_id}" --url="${site_url}" >/dev/null
  printf '%s: footer navigation %s (data: %s)\n' "${site_url}" "${footer_id}" "${content_path}"
}

while IFS=$'\t' read -r site_url footer_title content_file; do
  [[ -z "${site_url}" || "${site_url}" == \#* ]] && continue

  if [[ -z "${footer_title}" || -z "${content_file}" ]]; then
    printf 'Invalid footer data row for %s. Expected tab-separated site_url, title and content_file.\n' "${site_url}" >&2
    exit 1
  fi

  create_or_select_footer "${site_url}" "${footer_title}" "${content_file}"
done < "${footer_sites_file}"
