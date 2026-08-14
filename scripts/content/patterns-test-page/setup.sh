#!/usr/bin/env bash
set -euo pipefail

# Create or refresh the developer-only page used to inspect the declared
# section patterns from Fanny's site design. The page content is assembled
# from WordPress's active pattern registry so the test page does not duplicate
# pattern markup in the repo.
script_dir="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd -P)"
project_dir="$(cd -- "${script_dir}/../../.." && pwd -P)"
pages_file="${script_dir}/page.tsv"
order_file="${script_dir}/sections-patterns-names.txt"

for required_file in "${pages_file}" "${order_file}"; do
	if [[ ! -f "${required_file}" ]]; then
		printf 'Missing patterns-test-page data file: %s\n' "${required_file}" >&2
		exit 1
	fi
done

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

pattern_order_php='array('
pattern_count=0
while IFS= read -r pattern_name || [[ -n "${pattern_name}" ]]; do
	[[ -z "${pattern_name}" || "${pattern_name}" == \#* ]] && continue
	if [[ "${pattern_name}" == *"'"* ]]; then
		printf 'Pattern name contains an unsupported quote: %s\n' "${pattern_name}" >&2
		exit 1
	fi
	pattern_order_php+="'${pattern_name}',"
	pattern_count=$(( pattern_count + 1 ))
done < "${order_file}"
pattern_order_php+=')'

if [[ "${pattern_count}" -eq 0 ]]; then
	printf 'No patterns declared in %s.\n' "${order_file}" >&2
	exit 1
fi

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
\$pattern_order = ${pattern_order_php};
\$page_title = ${page_title_php};
\$page_slug = ${page_slug_php};
\$post_status = ${post_status_php};
\$post_author = ${post_author};
\$comment_status = ${comment_status_php};
\$ping_status = ${ping_status_php};
\$patterns_by_name = array();

foreach ( WP_Block_Patterns_Registry::get_instance()->get_all_registered() as \$pattern ) {
	if ( isset( \$pattern['name'], \$pattern['content'] ) ) {
		\$patterns_by_name[ \$pattern['name'] ] = \$pattern['content'];
	}
}

\$page_content = '';
foreach ( \$pattern_order as \$pattern_name ) {
	if ( ! isset( \$patterns_by_name[ \$pattern_name ] ) ) {
		WP_CLI::error( 'Active theme pattern is missing: ' . \$pattern_name );
	}

	\$page_content .= \$patterns_by_name[ \$pattern_name ] . "\\n";
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

printf 'Sections patterns test page generated from %s patterns in %s.\n' "${pattern_count}" "${order_file}"
