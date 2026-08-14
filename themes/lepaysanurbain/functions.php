<?php
/**
 * Theme bootstrap for Le Paysan Urbain.
 *
 * @package Le_Paysan_Urbain
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueue the shared theme stylesheet on the front end and in the editor.
 *
 * @return void
 */
function lpu_enqueue_theme_styles() {
	$theme = wp_get_theme();
	$version = $theme->get( 'Version' );

	wp_enqueue_style(
		'lpu-theme',
		get_theme_file_uri( 'assets/css/theme.css' ),
		array(),
		$version
	);
}
add_action( 'wp_enqueue_scripts', 'lpu_enqueue_theme_styles' );
add_action( 'enqueue_block_editor_assets', 'lpu_enqueue_theme_styles' );

/**
 * Enqueue the small front-end behavior that completes the shared menu
 * contract.
 *
 * @return void
 */
function lpu_enqueue_navigation_script() {
	$theme = wp_get_theme();

	wp_enqueue_script(
		'lpu-navigation',
		get_theme_file_uri( 'assets/js/navigation.js' ),
		array(),
		$theme->get( 'Version' ),
		true
	);
}
add_action( 'wp_enqueue_scripts', 'lpu_enqueue_navigation_script' );

/**
 * Return the official horizontal écru logo available for the current site.
 * Lyon intentionally has no entry until the incorrect delivered source is
 * replaced by a correct territorial variant.
 *
 * @return string
 */
function lpu_transparent_logo_file() {
	if ( is_main_site() ) {
		return 'network-horizontal-ecru-baseline.svg';
	}

	$files = array(
		2 => 'paris-horizontal-ecru-baseline.svg',
		4 => 'marseille-horizontal-ecru-baseline.svg',
	);

	return $files[ get_current_blog_id() ] ?? '';
}

/**
 * Whether the current page explicitly requests the transparent header.
 *
 * @return bool
 */
function lpu_is_transparent_header() {
	if ( ! is_singular( 'page' ) ) {
		return false;
	}

	return (bool) get_post_meta(
		get_queried_object_id(),
		'lpu_header_transparent',
		true
	) && '' !== lpu_transparent_logo_file();
}

/**
 * Register the per-page transparent-header setting in the block editor REST
 * schema as well as in the classic meta-box compatibility layer.
 *
 * @return void
 */
function lpu_register_header_meta() {
	register_post_meta(
		'page',
		'lpu_header_transparent',
		array(
			'type'              => 'boolean',
			'single'            => true,
			'default'           => false,
			'show_in_rest'      => true,
			'sanitize_callback' => 'rest_sanitize_boolean',
			'auth_callback'     => function ( $allowed, $meta_key, $post_id, $user_id ) {
				return user_can( $user_id, 'edit_post', $post_id );
			},
		)
	);
}
add_action( 'init', 'lpu_register_header_meta' );

/**
 * Add the explicit header variant control to pages.
 *
 * @return void
 */
function lpu_add_header_meta_box() {
	add_meta_box(
		'lpu-header-settings',
		'Header de la page',
		'lpu_render_header_meta_box',
		'page',
		'side',
		'default'
	);
}
add_action( 'add_meta_boxes_page', 'lpu_add_header_meta_box' );

/**
 * Render the explicit transparent-header setting.
 *
 * @param WP_Post $post Current page.
 * @return void
 */
function lpu_render_header_meta_box( $post ) {
	wp_nonce_field( 'lpu_save_header_settings', 'lpu_header_settings_nonce' );

	$logo_file = lpu_transparent_logo_file();
	if ( '' === $logo_file ) {
		echo '<p>La variante transparente est indisponible sur ce site tant que le logo horizontal écru territorial correct n’est pas livré.</p>';
		return;
	}

	$enabled = (bool) get_post_meta( $post->ID, 'lpu_header_transparent', true );
	printf(
		'<label><input type="checkbox" name="lpu_header_transparent" value="1" %1$s /> Header transparent sur le hero (état initial)</label>',
		checked( $enabled, true, false )
	);
	echo '<p>À utiliser uniquement si un hero contrasté se trouve directement derrière le header.</p>';
}

/**
 * Save the explicit transparent-header setting.
 *
 * @param int $post_id Page ID.
 * @return void
 */
function lpu_save_header_meta( $post_id ) {
	if (
		! isset( $_POST['lpu_header_settings_nonce'] )
		|| ! wp_verify_nonce(
			sanitize_text_field( wp_unslash( $_POST['lpu_header_settings_nonce'] ) ),
			'lpu_save_header_settings'
		)
		|| ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
		|| wp_is_post_revision( $post_id )
		|| ! current_user_can( 'edit_post', $post_id )
	) {
		return;
	}

	if ( '' === lpu_transparent_logo_file() ) {
		delete_post_meta( $post_id, 'lpu_header_transparent' );
		return;
	}

	if ( isset( $_POST['lpu_header_transparent'] ) ) {
		update_post_meta( $post_id, 'lpu_header_transparent', true );
	} else {
		delete_post_meta( $post_id, 'lpu_header_transparent' );
	}
}
add_action( 'save_post_page', 'lpu_save_header_meta' );

/**
 * Add the transparent state to the page body when explicitly enabled.
 *
 * @param array $classes Body classes.
 * @return array
 */
function lpu_add_header_body_class( $classes ) {
	if ( lpu_is_transparent_header() ) {
		$classes[] = 'lpu-header-transparent';
	}

	return $classes;
}
add_filter( 'body_class', 'lpu_add_header_body_class' );

/**
 * Swap the shared Site Logo to the official écru variant in the transparent
 * state. The opaque state continues to use the per-site custom_logo setting.
 *
 * @param string $block_content Rendered block HTML.
 * @param array  $block         Parsed block.
 * @return string
 */
function lpu_render_transparent_site_logo( $block_content, $block ) {
	if (
		! lpu_is_transparent_header()
		|| 'core/site-logo' !== ( $block['blockName'] ?? '' )
		|| false === strpos( $block_content, 'lpu-header__logo' )
	) {
		return $block_content;
	}

	$logo_file = lpu_transparent_logo_file();
	$logo_url  = get_theme_file_uri( 'assets/images/logos/' . $logo_file );
	$opaque_url = '';

	if ( preg_match( '/<img\\b[^>]*\\bsrc="([^"]+)"/i', $block_content, $matches ) ) {
		$opaque_url = $matches[1];
	}

	$block_content = preg_replace(
		'/(<img\\b)/i',
		'$1 data-lpu-opaque-src="' . esc_attr( $opaque_url ) . '" data-lpu-transparent-src="' . esc_attr( $logo_url ) . '"',
		$block_content,
		1
	);

	$block_content = preg_replace(
		'/(<img\\b[^>]*\\bsrc=")[^"]*(")/i',
		'$1' . esc_url( $logo_url ) . '$2',
		$block_content,
		1
	);

	return preg_replace(
		'/(<img\\b[^>]*\\bsrcset=")[^"]*(")/i',
		'$1' . esc_url( $logo_url ) . '$2',
		$block_content,
		1
	);
}
add_filter( 'render_block', 'lpu_render_transparent_site_logo', 10, 2 );

/**
 * Allow the provisioning script to import the official, theme-owned SVG
 * logos. This is intentionally limited to WP-CLI; regular media uploads do
 * not gain a new file type from the theme.
 *
 * @param array $mimes Allowed upload MIME types.
 * @return array
 */
function lpu_allow_cli_svg_upload( $mimes ) {
	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		$mimes['svg'] = 'image/svg+xml';
	}

	return $mimes;
}
add_filter( 'upload_mimes', 'lpu_allow_cli_svg_upload' );

/**
 * WordPress may identify an SVG as text during the CLI import preflight.
 * Treat SVG files as the official theme logo type in that same CLI-only
 * provisioning context.
 *
 * @param array       $data      File type data from WordPress.
 * @param string      $file      Temporary file path.
 * @param string      $filename  Original filename.
 * @param array       $mimes     Allowed MIME types.
 * @param string|false $real_mime MIME detected from file contents.
 * @return array
 */
function lpu_allow_cli_svg_filetype( $data, $file, $filename, $mimes, $real_mime ) {
	$extension = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );

	if ( defined( 'WP_CLI' ) && WP_CLI && 'svg' === $extension ) {
		$data['ext']  = 'svg';
		$data['type'] = 'image/svg+xml';
	}

	return $data;
}
add_filter( 'wp_check_filetype_and_ext', 'lpu_allow_cli_svg_filetype', 10, 5 );

/**
 * Bind the shared header Navigation block to the navigation selected for the
 * current site. Navigation posts are site-local in a multisite network, so a
 * numeric ref cannot be committed to the shared template part.
 *
 * @param array         $parsed_block The block being prepared for rendering.
 * @param array         $source_block The original parsed block.
 * @param WP_Block|null $parent_block The parent block, if any.
 * @return array
 */
function lpu_bind_site_navigation( $parsed_block, $source_block, $parent_block ) {
	if ( 'core/navigation' !== ( $parsed_block['blockName'] ?? '' ) ) {
		return $parsed_block;
	}

	if ( isset( $parsed_block['attrs']['ref'] ) ) {
		return $parsed_block;
	}

	$navigation_id = absint( get_option( 'lpu_navigation_id', 0 ) );
	if ( ! $navigation_id ) {
		return $parsed_block;
	}

	$navigation = get_post( $navigation_id );
	if ( ! $navigation || 'wp_navigation' !== $navigation->post_type || 'publish' !== $navigation->post_status ) {
		return $parsed_block;
	}

	$parsed_block['attrs']['ref'] = $navigation_id;

	return $parsed_block;
}
add_filter( 'render_block_data', 'lpu_bind_site_navigation', 10, 3 );
