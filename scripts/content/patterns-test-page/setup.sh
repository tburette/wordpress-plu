#!/usr/bin/env bash
set -euo pipefail

# Create or refresh the developer-only page used to inspect every pattern
# provided by the active theme. The page content is assembled from WordPress's
# pattern registry so the test page does not duplicate pattern markup in the
# repository or require a second list of pattern names.
#
# The managed page is published at /lpu-sections-patterns-test/ on the network
# site and is refreshed when it already exists. Patterns are filtered to the
# active theme and sorted by name for deterministic output. This page is a
# developer fixture only: it does not configure the network Home, farm links,
# header transparency or editorial media.
script_dir="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd -P)"
project_dir="$(cd -- "${script_dir}/../../.." && pwd -P)"
pages_file="${script_dir}/page.tsv"

if [[ ! -f "${pages_file}" ]]; then
	printf 'Missing patterns-test-page data file: %s\n' "${pages_file}" >&2
	exit 1
fi

cd -- "${project_dir}"

run_wp() {
	wp-env run cli wp "$@"
}

php_quote() {
	local value="${1}"
	value="${value//\\/\\\\}"
	value="${value//\'/\\\'}"
	printf "'%s'" "${value}"
}

while IFS='|' read -r site_url page_title page_slug post_status post_author comment_status ping_status <&3; do
	[[ -z "${site_url}" || "${site_url}" == \#* ]] && continue

	if [[ -z "${page_title}" || -z "${page_slug}" || -z "${post_status}" || -z "${post_author}" || -z "${comment_status}" || -z "${ping_status}" ]]; then
		printf 'Invalid patterns-test-page data row for %s. Expected site_url|title|slug|status|author|comment_status|ping_status.\n' "${site_url}" >&2
		exit 1
	fi

	if [[ ! "${post_author}" =~ ^[0-9]+$ ]]; then
		printf 'Invalid post author for %s: %s\n' "${site_url}" "${post_author}" >&2
		exit 1
	fi

	page_title_php="$(php_quote "${page_title}")"
	page_slug_php="$(php_quote "${page_slug}")"
	post_status_php="$(php_quote "${post_status}")"
	comment_status_php="$(php_quote "${comment_status}")"
	ping_status_php="$(php_quote "${ping_status}")"

	read -r -d '' php_code <<PHP || true
\$page_title = ${page_title_php};
\$page_slug = ${page_slug_php};
\$post_status = ${post_status_php};
\$post_author = ${post_author};
\$comment_status = ${comment_status_php};
\$ping_status = ${ping_status_php};
\$patterns_by_name = array();
\$theme_namespace = trailingslashit( get_stylesheet() );

foreach ( WP_Block_Patterns_Registry::get_instance()->get_all_registered() as \$pattern ) {
	if ( ! isset( \$pattern['name'], \$pattern['content'] ) ) {
		continue;
	}

	\$source = isset( \$pattern['source'] ) ? (string) \$pattern['source'] : '';
	\$is_theme_pattern = 'theme' === \$source || 0 === strpos( \$pattern['name'], \$theme_namespace );
	if ( ! \$is_theme_pattern ) {
		continue;
	}

	\$patterns_by_name[ \$pattern['name'] ] = \$pattern['content'];
}

ksort( \$patterns_by_name, SORT_NATURAL | SORT_FLAG_CASE );
if ( ! \$patterns_by_name ) {
	WP_CLI::error( 'No patterns provided by the active theme.' );
}

\$page_content = '';
foreach ( \$patterns_by_name as \$pattern_content ) {
	\$page_content .= \$pattern_content . "\\n";
}

\$pages = get_posts(
	array(
		'post_type'      => 'page',
		'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future', 'trash' ),
		'name'           => \$page_slug,
		'posts_per_page' => 2,
		'orderby'        => 'ID',
		'order'          => 'ASC',
	)
);

if ( count( \$pages ) > 1 ) {
	WP_CLI::error( 'More than one page uses the sections patterns test slug: ' . \$page_slug );
}

if ( \$pages ) {
	\$page = \$pages[0];

	if ( 'trash' === \$page->post_status && ! wp_untrash_post( \$page->ID ) ) {
		WP_CLI::error( 'Could not restore the sections patterns test page: ' . \$page->ID );
	}

	\$page_id = wp_update_post(
		array(
			'ID'           => \$page->ID,
			'post_title'   => \$page_title,
			'post_content' => \$page_content,
		),
		true
	);
	\$action = 'updated';
} else {
	\$page_id = wp_insert_post(
		array(
			'post_title'     => \$page_title,
			'post_name'      => \$page_slug,
			'post_status'    => \$post_status,
			'post_author'    => \$post_author,
			'post_content'   => \$page_content,
			'post_type'      => 'page',
			'comment_status' => \$comment_status,
			'ping_status'    => \$ping_status,
		),
		true
	);
	\$action = 'created';
}

if ( is_wp_error( \$page_id ) ) {
	WP_CLI::error( \$page_id->get_error_message() );
}

echo get_home_url() . ': ' . \$action . ' sections patterns test page ' . \$page_id . ' (' . get_permalink( \$page_id ) . ")\\n";
PHP

	run_wp eval "${php_code}" --url="${site_url}"
done 3< "${pages_file}"

printf 'Sections patterns test page generated from every pattern provided by the active theme.\n'
