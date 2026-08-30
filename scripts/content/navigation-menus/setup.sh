#!/usr/bin/env bash
set -euo pipefail

# Provisions for each site:
# - The main menu (a wp_navigation).
# - The `header` template part from parts/ is inserted in the DB using override

script_dir="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd -P)"
project_dir="$(cd -- "$script_dir/../../.." && pwd -P)"
navigation_sites_file="$script_dir/navigation-sites.tsv"
header_template_file="$project_dir/themes/lepaysanurbain/parts/header.html"
navigation_menu_name="menu-principal"

if [[ ! -f "$navigation_sites_file" ]]; then
  printf 'Missing navigation data file: %s\n' "$navigation_sites_file" >&2
  exit 1
fi

cd -- "$project_dir"

run_wp() {
  # Keep WP-CLI from consuming the TSV currently driving the loop below.
  # may cause this script to fail if invoked directly from a terminal
  wp-env run cli wp "$@" </dev/null
}

# return the header template with the ref to the navigation menu
# It is modified to add the appropriate menu ref.
render_header_template_part() {
  local navigation_id="$1"

  if [[ ! -f "$header_template_file" ]]; then
    printf 'Missing shared header template part: %s\n' "$header_template_file" >&2
    exit 1
  fi

  # The theme fallback deliberately has no site-specific ref. The database
  # template-part database override receives the local Navigation ID here.
  sed "0,/wp:navigation {/s//wp:navigation {\"ref\":$navigation_id,/" \
    "$header_template_file"
}

create_or_update_header_template_part() {
  local site_url="$1"
  local navigation_id="$2"
  local template_content
  local template_id

  template_content="$(render_header_template_part "$navigation_id")"
  template_id="$(run_wp post list \
    --url="$site_url" \
    --post_type=wp_template_part \
    --post_status=any \
    --name=header \
    --posts_per_page=1 \
    --field=ID | tr -d '\r')"

  if [[ -z "$template_id" ]]; then
    template_id="$(run_wp post create \
      --url="$site_url" \
      --post_type=wp_template_part \
      --post_status=publish \
      --post_title="En-tête" \
      --post_name=header \
      --post_content="$template_content" \
      --porcelain | tr -d '\r')"
    printf '%s: created native header template part %s\n' "$site_url" "$template_id"
  else
    run_wp post update "$template_id" \
      --url="$site_url" \
      --post_content="$template_content" >/dev/null
    printf '%s: updated native header template part %s\n' "$site_url" "$template_id"
  fi

  # wp_theme taxonomy states which theme this element is linked with. This
  # avoids this element from still being used if another theme is used, which
  # could happen because it remains in the DB after theme switch.
  # wp_template_part_area taxonomy : header, footer, sidebar or uncategorized. 
  # Where it can be used. group by area in the Site Editor.
  run_wp eval \
    "wp_set_post_terms($template_id, 'lepaysanurbain', 'wp_theme', false); wp_set_post_terms($template_id, 'header', 'wp_template_part_area', false); update_post_meta($template_id, 'origin', 'theme');" \
    --url="$site_url" >/dev/null
}

# The menu content (block markup) is loaded from the HTML files in this directory. 
# Does not change the menu if it already exists (avoids risking losing user
# changes).
create_or_select_menu() {
  local site_url="$1"
  local navigation_title="$2"
  local content_file="$3"
  local menu_id
  local content_path="$script_dir/$content_file"

  if [[ ! -f "$content_path" ]]; then
    printf 'Missing navigation content file: %s\n' "$content_path" >&2
    exit 1
  fi

  local menu_content
  menu_content="$(<"$content_path")"

  menu_id="$(run_wp post list \
    --url="$site_url" \
    --post_type=wp_navigation \
    --post_status=any \
    --name="$navigation_menu_name" \
    --posts_per_page=1 \
    --field=ID | tr -d '\r')"

  if [[ -z "$menu_id" ]]; then
    menu_id="$(run_wp post create \
      --url="$site_url" \
      --post_type=wp_navigation \
      --post_status=publish \
      --post_title="$navigation_title" \
      --post_name="$navigation_menu_name" \
      --post_content="$menu_content" \
      --porcelain | tr -d '\r')"
  fi

  echo "$menu_id"
}

while IFS=$'\t' read -r site_url navigation_title content_file; do
  # line is an empty line or a comment
  [[ -z "$site_url" || "$site_url" == \#* ]] && continue

  if [[ -z "$navigation_title" || -z "$content_file" ]]; then
    printf 'Invalid navigation data row for %s. Expected tab-separated site_url, title and content_file.\n' "$site_url" >&2
    exit 1
  fi

  menu_id=$(create_or_select_menu "$site_url" "$navigation_title" "$content_file")
  printf '%s: navigation %s (data: %s)\n' "$site_url" "$menu_id" "$content_file"
  create_or_update_header_template_part "$site_url" "$menu_id"
done < "$navigation_sites_file"
