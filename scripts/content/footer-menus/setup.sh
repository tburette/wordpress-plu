#!/usr/bin/env bash
set -euo pipefail

# Create or select one site-local footer Navigation post for each row in
# footer-sites.tsv. The fragments provide the three visual footer columns and
# their secondary legal-information group; the shared visual frame lives in
# themes/lepaysanurbain/parts/footer.html. Existing footer content is preserved.
#
# The script also creates the native, site-local footer template-part override.
# That override contains the ref to this site's footer Navigation post, so the
# shared theme does not need to inspect options or rewrite Navigation blocks at
# render time. Destinations for pages that do not exist yet remain provisional
# until their editorial URLs are validated.
script_dir="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd -P)"
project_dir="$(cd -- "${script_dir}/../../.." && pwd -P)"
footer_sites_file="${script_dir}/footer-sites.tsv"
footer_template_file="${project_dir}/themes/lepaysanurbain/parts/footer.html"

if [[ ! -f "${footer_sites_file}" ]]; then
  printf 'Missing footer navigation data file: %s\n' "${footer_sites_file}" >&2
  exit 1
fi

cd -- "${project_dir}"

run_wp() {
  wp-env run cli wp "$@" </dev/null
}

render_footer_template_part() {
  local navigation_id="$1"

  if [[ ! -f "${footer_template_file}" ]]; then
    printf 'Missing shared footer template part: %s\n' "${footer_template_file}" >&2
    exit 1
  fi

  # The theme fallback deliberately has no site-specific ref. The database
  # template-part override receives the local Navigation ID here.
  sed "0,/wp:navigation {/s//wp:navigation {\"ref\":${navigation_id},/" \
    "${footer_template_file}"
}

ensure_footer_template_part() {
  local site_url="$1"
  local navigation_id="$2"
  local template_content
  local template_id
  local template_origin

  template_content="$(render_footer_template_part "${navigation_id}")"
  template_id="$(run_wp post list \
    --url="${site_url}" \
    --post_type=wp_template_part \
    --post_status=any \
    --name=footer \
    --posts_per_page=1 \
    --field=ID | tr -d '\r')"

  if [[ -z "${template_id}" ]]; then
    template_id="$(run_wp post create \
      --url="${site_url}" \
      --post_type=wp_template_part \
      --post_status=publish \
      --post_title="Pied de page" \
      --post_name=footer \
      --post_content="${template_content}" \
      --porcelain | tr -d '\r')"
    printf '%s: created native footer template part %s\n' "${site_url}" "${template_id}"
  else
    template_origin="$(run_wp post meta get "${template_id}" origin --single --url="${site_url}" 2>/dev/null | tr -d '\r' || true)"

    if [[ -z "${template_origin}" || "theme" == "${template_origin}" ]]; then
      run_wp post update "${template_id}" \
        --url="${site_url}" \
        --post_content="${template_content}" >/dev/null
      printf '%s: updated native footer template part %s\n' "${site_url}" "${template_id}"
    else
      printf '%s: preserved custom footer template part %s; associate it with Navigation %s manually\n' \
        "${site_url}" "${template_id}" "${navigation_id}" >&2
      return
    fi
  fi

  run_wp eval \
    "wp_set_post_terms(${template_id}, 'lepaysanurbain', 'wp_theme', false); wp_set_post_terms(${template_id}, 'footer', 'wp_template_part_area', false); update_post_meta(${template_id}, 'origin', 'theme');" \
    --url="${site_url}" >/dev/null
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

  ensure_footer_template_part "${site_url}" "${footer_id}"
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
