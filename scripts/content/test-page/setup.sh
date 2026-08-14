#!/usr/bin/env bash
set -euo pipefail

# Restore the small page used to exercise the theme's typography, buttons,
# groups, widths, colors, and decorative classes. The page data is kept in
# page.tsv and the Gutenberg markup is kept in the adjacent HTML file.
script_dir="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd -P)"
project_dir="$(cd -- "${script_dir}/../../.." && pwd -P)"
pages_file="${script_dir}/page.tsv"

if [[ ! -f "${pages_file}" ]]; then
  printf 'Missing test-page data file: %s\n' "${pages_file}" >&2
  exit 1
fi

cd -- "${project_dir}"

run_wp() {
  # Keep WP-CLI from consuming the TSV currently driving the loop below.
  wp-env run cli wp "$@" </dev/null
}

while IFS='|' read -r site_url post_type post_status post_title post_slug post_author post_date comment_status ping_status content_file; do
  [[ -z "${site_url}" || "${site_url}" == \#* ]] && continue

  if [[ -z "${post_type}" || -z "${post_status}" || -z "${post_title}" || -z "${post_slug}" || -z "${post_author}" || -z "${post_date}" || -z "${comment_status}" || -z "${ping_status}" || -z "${content_file}" ]]; then
    printf 'Invalid test-page data row for %s.\n' "${site_url}" >&2
    exit 1
  fi

  content_path="${script_dir}/${content_file}"
  if [[ ! -f "${content_path}" ]]; then
    printf 'Missing test-page content file: %s\n' "${content_path}" >&2
    exit 1
  fi

  page_content="$(<"${content_path}")"

  existing_count="$(run_wp post list \
    --url="${site_url}" \
    --post_type="${post_type}" \
    --post_status=any \
    --name="${post_slug}" \
    --posts_per_page=1 \
    --format=count | tr -d '\r')"

  if [[ "${existing_count}" != "0" ]]; then
    printf '%s: page with slug %s already exists; leaving it unchanged.\n' "${site_url}" "${post_slug}"
    continue
  fi

  run_wp post create \
    --url="${site_url}" \
    --post_author="${post_author}" \
    --post_date="${post_date}" \
    --post_content="${page_content}" \
    --post_title="${post_title}" \
    --post_status="${post_status}" \
    --post_type="${post_type}" \
    --comment_status="${comment_status}" \
    --ping_status="${ping_status}" \
    --post_name="${post_slug}" \
    --porcelain >/dev/null

  printf '%s: restored page from %s.\n' "${site_url}" "${content_path}"
done < "${pages_file}"
