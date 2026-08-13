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
