#!/usr/bin/env bash
set -euo pipefail

# Import the official theme-owned SVG and select it as the Site Logo on each
# multisite blog.
# The opaque header uses the horizontal green logotype with baseline; the
# corresponding écru variants are available in the theme for transparent-header
# pages. 
script_dir="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd -P)"
project_dir="$(cd -- "${script_dir}/../../.." && pwd -P)"
logos_file="${script_dir}/logos-sites.tsv"
logos_dir="${project_dir}/themes/lepaysanurbain/assets/images/logos"
container_logos_dir="/var/www/html/wp-content/themes/lepaysanurbain/assets/images/logos"

if [[ ! -f "${logos_file}" ]]; then
	printf 'Missing logo data file: %s\n' "${logos_file}" >&2
	exit 1
fi

cd -- "${project_dir}"

run_wp() {
	# Keep WP-CLI from consuming the TSV currently driving the loop below.
	wp-env run cli wp "$@" </dev/null
}

select_logo() {
	local site_url="$1"
	local logo_title="$2"
	local logo_file="$3"
	local host_logo_path="${logos_dir}/${logo_file}"
	local container_logo_path="${container_logos_dir}/${logo_file}"
	local attachment_id

	if [[ ! -f "${host_logo_path}" ]]; then
		printf 'Missing logo asset: %s\n' "${host_logo_path}" >&2
		exit 1
	fi

	attachment_id="$(run_wp post list \
		--url="${site_url}" \
		--post_type=attachment \
		--post_status=inherit \
		--post_mime_type=image/svg+xml \
		--search="${logo_title}" \
		--posts_per_page=1 \
		--orderby=ID \
		--order=DESC \
		--field=ID | tr -d '\r')"

	if [[ -z "${attachment_id}" ]]; then
		attachment_id="$(run_wp media import "${container_logo_path}" \
			--url="${site_url}" \
			--title="${logo_title}" \
			--porcelain | tr -d '\r')"
	fi

	if [[ ! "${attachment_id}" =~ ^[0-9]+$ ]]; then
		printf 'Could not select or import logo %s for %s (got %s).\n' \
			"${logo_file}" "${site_url}" "${attachment_id}" >&2
		exit 1
	fi

	run_wp theme mod set custom_logo "${attachment_id}" --url="${site_url}" >/dev/null
	printf '%s: custom_logo %s (%s)\n' "${site_url}" "${attachment_id}" "${logo_file}"
}

while IFS=$'\t' read -r site_url logo_title logo_file; do
	[[ -z "${site_url}" || "${site_url}" == \#* ]] && continue

	if [[ -z "${logo_title}" || -z "${logo_file}" ]]; then
		printf 'Invalid logo data row for %s. Expected tab-separated site_url, logo_title and logo_file.\n' "${site_url}" >&2
		exit 1
	fi

	select_logo "${site_url}" "${logo_title}" "${logo_file}"
done < "${logos_file}"
