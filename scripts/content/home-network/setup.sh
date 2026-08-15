#!/usr/bin/env bash
set -euo pipefail

# Assemble the network Home from the active theme patterns. The section order
# is kept beside this script because it describes this Home fixture only. The
# operation refuses to replace an editorial Home by default.
#
# This is the network-only Home content operation. It resolves the current
# Paris, Lyon and Marseille site URLs from the multisite, enables the explicit
# lpu_header_transparent page setting, and transforms the second cards
# occurrence into the green-grey, titles-only variant used by the Home.
# The patterns still use the local SVG placeholder until authorized editorial
# media are imported. Use --force only to intentionally replace an already
# assembled or edited Home.
script_dir="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd -P)"
project_dir="$(cd -- "${script_dir}/../../.." && pwd -P)"
pages_file="${script_dir}/page.tsv"
order_file="${script_dir}/home-sections-names.txt"

usage() {
	printf 'Usage: %s [--force]\n' "${0}"
	printf '  --force  replace an existing Home whose content is not the technical placeholder\n'
}

force=false
if [[ "${1:-}" == "--force" && "$#" -eq 1 ]]; then
	force=true
elif [[ "$#" -gt 0 ]]; then
	usage >&2
	exit 1
fi

for required_file in "${pages_file}" "${order_file}"; do
	if [[ ! -f "${required_file}" ]]; then
		printf 'Missing Home data file: %s\n' "${required_file}" >&2
		exit 1
	fi
done

cd -- "${project_dir}"

run_wp() {
	# Keep WP-CLI from consuming the TSV currently driving the loop below.
	wp-env run cli wp "$@" </dev/null
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

while IFS='|' read -r site_url page_title page_slug post_status <&3; do
	[[ -z "${site_url}" || "${site_url}" == \#* ]] && continue

	if [[ -z "${page_title}" || -z "${page_slug}" || -z "${post_status}" ]]; then
		printf 'Invalid Home data row for %s. Expected site_url|title|slug|status.\n' "${site_url}" >&2
		exit 1
	fi

	page_title_php="$(php_quote "${page_title}")"
	page_slug_php="$(php_quote "${page_slug}")"
	post_status_php="$(php_quote "${post_status}")"
	force_php='false'
	if [[ "${force}" == true ]]; then
		force_php='true'
	fi

	read -r -d '' php_code <<PHP || true
\$pattern_order = ${pattern_order_php};
\$page_title = ${page_title_php};
\$page_slug = ${page_slug_php};
\$post_status = ${post_status_php};
\$force = ${force_php};

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
	WP_CLI::error( 'More than one page uses the Home slug: ' . \$page_slug );
}

if ( ! \$pages ) {
	WP_CLI::error( 'The technical front page does not exist: ' . \$page_slug . '. Run npm run env:content -- front-pages first.' );
}

\$page = \$pages[0];
if ( 'trash' === \$page->post_status && ! wp_untrash_post( \$page->ID ) ) {
	WP_CLI::error( 'Could not restore the network Home page: ' . \$page->ID );
}

if ( (string) \$page->post_title !== \$page_title ) {
	WP_CLI::error( 'The page title is not the expected Home title: ' . \$page->post_title );
}

if ( 'page' !== get_option( 'show_on_front' ) || (int) get_option( 'page_on_front' ) !== (int) \$page->ID ) {
	WP_CLI::error( 'The expected Home page is not the current page_on_front. Run npm run env:content -- front-pages first.' );
}

\$technical_placeholder = '<!--
  This page intentionally starts without visible content.
  Home sections will be assembled in Gutenberg from the theme patterns.
-->';
if ( ! \$force && trim( (string) \$page->post_content ) !== trim( \$technical_placeholder ) ) {
	WP_CLI::error( 'The Home already contains editorial content. Re-run with --force only when replacement is intentional.' );
}

\$patterns_by_name = array();
foreach ( WP_Block_Patterns_Registry::get_instance()->get_all_registered() as \$pattern ) {
	if ( isset( \$pattern['name'], \$pattern['content'] ) ) {
	    \$patterns_by_name[ \$pattern['name'] ] = \$pattern['content'];
	}
}

\$farm_urls = array();
\$farm_labels = array(
	'paris'     => 'Paris',
	'lyon'      => 'Lyon',
	'marseille' => 'Marseille',
);
foreach ( get_sites( array( 'number' => 100 ) ) as \$site ) {
	\$site_home = trailingslashit( get_home_url( (int) \$site->blog_id ) );
	\$host = strtolower( (string) wp_parse_url( \$site_home, PHP_URL_HOST ) );
	\$host_parts = explode( '.', \$host );
	\$site_key = \$host_parts[0] ?? '';
	if ( isset( \$farm_labels[ \$site_key ] ) ) {
	    \$farm_urls[ \$farm_labels[ \$site_key ] ] = \$site_home;
	}
}

foreach ( \$farm_labels as \$label ) {
	if ( ! isset( \$farm_urls[ \$label ] ) ) {
	    WP_CLI::error( 'Could not resolve the multisite URL for farm: ' . \$label );
	}
}

\$page_content = '';
\$cards_occurrences = 0;
foreach ( \$pattern_order as \$pattern_name ) {
	if ( ! isset( \$patterns_by_name[ \$pattern_name ] ) ) {
	    WP_CLI::error( 'Active theme pattern is missing: ' . \$pattern_name );
	}

	\$section = \$patterns_by_name[ \$pattern_name ];
	if ( 'lepaysanurbain/network-farm-selector' === \$pattern_name ) {
		foreach ( \$farm_labels as \$label ) {
			\$needle = '<a>' . \$label . '</a>';
			\$replacement = '<a href="' . esc_url( \$farm_urls[ \$label ] ) . '">' . \$label . '</a>';
			\$section = str_replace( \$needle, \$replacement, \$section, \$link_count );
			if ( 1 !== \$link_count ) {
				WP_CLI::error( 'Expected one unconfigured farm link for ' . \$label );
			}
		}
	}

	if ( 'lepaysanurbain/cards' === \$pattern_name ) {
		\$cards_occurrences++;
		if ( 2 === \$cards_occurrences ) {
			\$section = str_replace(
				'"backgroundColor":"ecru","className":"lpu-band lpu-card-grid lpu-motif lpu-motif-1-bandeau"',
				'"backgroundColor":"vert-grise","className":"lpu-band lpu-card-grid lpu-card-grid--titles-only"',
				\$section,
				\$outer_attribute_count
			);
			if ( 1 !== \$outer_attribute_count ) {
				WP_CLI::error( 'Expected the second cards pattern wrapper attributes.' );
			}

			\$section = str_replace(
				'lpu-card-grid lpu-motif lpu-motif-1-bandeau has-ecru-background-color has-background',
				'lpu-card-grid lpu-card-grid--titles-only has-vert-grise-background-color has-background',
				\$section,
				\$outer_class_count
			);
			if ( 1 !== \$outer_class_count ) {
				WP_CLI::error( 'Expected the second cards pattern wrapper classes.' );
			}

			\$section = str_replace( '>Des façons d’agir<', '>Le réseau en action<', \$section, \$title_count );
			if ( 1 !== \$title_count ) {
				WP_CLI::error( 'Expected the second cards pattern title.' );
			}

			\$heading_replacements = array(
				'>Particuliers<'                  => '>Activités et événements<',
				'>Professionnels<'                => '>Production locale<',
				'>Partenaires et institutions<'   => '>Projets et insertion<',
			);
			foreach ( \$heading_replacements as \$from => \$to ) {
				\$section = str_replace( \$from, \$to, \$section, \$heading_count );
				if ( 1 !== \$heading_count ) {
					WP_CLI::error( 'Expected one cards heading replacement for: ' . \$from );
				}
			}

			\$section = preg_replace( '/\\s*<!-- wp:paragraph\\b.*?<!-- \\/wp:paragraph -->/s', '', \$section, -1, \$paragraph_count );
			if ( null === \$section || 3 !== \$paragraph_count ) {
				WP_CLI::error( 'Expected three optional card descriptions in the second cards pattern.' );
			}

			\$section = preg_replace( '/\\s*<!-- wp:buttons\\b.*?<!-- \\/wp:buttons -->/s', '', \$section, -1, \$button_count );
			if ( null === \$section || 3 !== \$button_count ) {
				WP_CLI::error( 'Expected three optional card buttons in the second cards pattern.' );
			}
		}
	}

	\$page_content .= \$section . "\\n";
}

if ( 2 !== \$cards_occurrences ) {
	WP_CLI::error( 'The Home order must contain exactly two cards patterns.' );
}

\$updated_id = wp_update_post(
	array(
		'ID'           => \$page->ID,
		'post_content' => \$page_content,
		'post_status'  => \$post_status,
	),
	true
);
if ( is_wp_error( \$updated_id ) ) {
	WP_CLI::error( \$updated_id->get_error_message() );
}

update_post_meta( \$page->ID, 'lpu_header_transparent', true );
echo get_home_url() . ': assembled network Home ' . \$page->ID . ' (' . get_permalink( \$page->ID ) . ")\\n";
PHP

	run_wp eval "${php_code}" --url="${site_url}"
done 3< "${pages_file}"

printf 'Network Home assembled from %s patterns in %s.\n' "${pattern_count}" "${order_file}"
