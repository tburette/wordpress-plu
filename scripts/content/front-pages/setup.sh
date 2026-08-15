#!/usr/bin/env bash
set -euo pipefail

# Create or select the technical page used as the static front page on every
# site listed in sites.tsv. A newly created page receives the non-visible
# placeholder content from accueil.html; an existing page is never overwritten.
# The script only changes the two reading options required for WordPress to
# render that page at the site root. It does not add patterns, images or design
# sections; those are separate content operations.
#
# The slug is accueil and the title Accueil is used only when a page is first
# created. Existing pages are found in every non-trash status, so an editorial
# page in progress is selected rather than duplicated.
script_dir="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd -P)"
project_dir="$(cd -- "${script_dir}/../../.." && pwd -P)"
sites_file="${script_dir}/sites.tsv"
page_content_file="${script_dir}/accueil.html"

for required_file in "${sites_file}" "${page_content_file}"; do
  if [[ ! -f "${required_file}" ]]; then
    printf 'Missing front-page data file: %s\n' "${required_file}" >&2
    exit 1
  fi
done

cd -- "${project_dir}"

run_wp() {
  # Keep WP-CLI from consuming the TSV currently driving the loop below.
  wp-env run cli wp "$@" </dev/null
}

page_content="$(<"${page_content_file}")"

while IFS='|' read -r site_url page_title page_slug; do
  [[ -z "${site_url}" || "${site_url}" == \#* ]] && continue

  if [[ -z "${page_title}" || -z "${page_slug}" ]]; then
    printf 'Invalid front-page data row for %s. Expected site_url|title|slug.\n' "${site_url}" >&2
    exit 1
  fi

  page_id="$(run_wp post list \
    --url="${site_url}" \
    --post_type=page \
    --post_status=any \
    --name="${page_slug}" \
    --posts_per_page=1 \
    --field=ID | tr -d '\r')"

  if [[ -z "${page_id}" ]]; then
    page_id="$(run_wp post create \
      --url="${site_url}" \
      --post_type=page \
      --post_status=publish \
      --post_title="${page_title}" \
      --post_name="${page_slug}" \
      --post_content="${page_content}" \
      --porcelain | tr -d '\r')"
    printf '%s: created front page %s\n' "${site_url}" "${page_id}"
  else
    printf '%s: selected existing front page %s\n' "${site_url}" "${page_id}"
  fi

  run_wp option update show_on_front page --url="${site_url}" >/dev/null
  run_wp option update page_on_front "${page_id}" --url="${site_url}" >/dev/null
done < "${sites_file}"

printf 'Front pages are configured from %s.\n' "${sites_file}"
