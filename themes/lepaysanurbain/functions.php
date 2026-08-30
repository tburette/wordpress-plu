<?php

/**
 * Theme bootstrap for Le Paysan Urbain.
 *
 * @package Le_Paysan_Urbain
 */

if (! defined('ABSPATH')) {
	exit;
}

// Site logos (normal + transparent header variants).
require_once __DIR__ . '/inc/site-logos.php';

/**
 * Enqueue the shared theme stylesheet for the frontend and the editor
 * canvas. 
 */
function lpu_enqueue_theme_styles()
{
	$theme = wp_get_theme();
	$version = $theme->get('Version');
	wp_enqueue_style(
		'lpu-theme',
		get_theme_file_uri('assets/css/theme.css'),
		array(),
		$version
	);
}
// 'enqueue_block_assets' => applies to frontend + editor content but not the
// editor UI (sidebar,...)
add_action('enqueue_block_assets', 'lpu_enqueue_theme_styles');

/**
 * Enqueue the small header-layout observer.
 *
 * Core Navigation remains responsible for all menu interaction. This script
 * only chooses between the inline and overlay presentation when the native
 * menu no longer fits in its actual navigation rail.
 */
function lpu_enqueue_navigation_layout_script()
{
	wp_enqueue_script_module(
		'lpu-navigation-layout',
		get_theme_file_uri('assets/js/navigation.js'),
		array(),
		wp_get_theme()->get('Version'),
		array(
			'strategy'  => 'defer',
		)
	);
}
add_action('wp_enqueue_scripts', 'lpu_enqueue_navigation_layout_script');
add_action('admin_enqueue_scripts', 'lpu_enqueue_navigation_layout_script');

/**
 * Register the reusable section patterns category.
 * Otherwise they appear in an 'Uncategorized' patterns section.
 */
function lpu_register_pattern_categories()
{
	register_block_pattern_category(
		'lpu-sections',
		array(
			'label' => 'Sections Le Paysan Urbain',
		)
	);
}
add_action('init', 'lpu_register_pattern_categories');

/**
 * Keep the unsynced patterns inserted into post editable as normal blocks are.
 *
 * WordPress 7 enables contentOnly mode for unsynced patterns by default.
 * Reminder : on a container block, content-only mode restricts a user to
 * changing content (text, image, urls,..) of a block and its children. 
 * It prevents structural changes of the blocks inside (add a block, move a 
 * block,..) and also prevents the use of the block styling options usually 
 * available in the sidebar.
 * This enables the old behavior back where unsynced patterns are freely 
 * editable by default.
 *
 * Can activate contentOnly mode for individual unsynced patterns by using 
 * {"templateLock":"contentOnly"}
 *
 * @param array $settings Block editor settings.
 * @return array
 */
function lpu_disable_unsynced_pattern_content_only_by_default($settings)
{
	$settings['disableContentOnlyForUnsyncedPatterns'] = true;

	return $settings;
}
add_filter('block_editor_settings_all', 'lpu_disable_unsynced_pattern_content_only_by_default');

/**
 * Whether the current page requests the transparent header.
 *
 * @return bool
 */
function lpu_is_transparent_header()
{
	if (! is_singular('page')) {
		return false;
	}

	return (bool) get_post_meta(
		get_queried_object_id(),
		'lpu_header_transparent',
		true
	);
}

/**
 * Make the the per-page transparent-header setting available in the REST API.
 */
function lpu_register_header_meta()
{
	register_post_meta(
		'page',
		'lpu_header_transparent',
		array(
			'type'              => 'boolean',
			'single'            => true,
			'default'           => false,
			'show_in_rest'      => true,
			'sanitize_callback' => 'rest_sanitize_boolean',
			'auth_callback'     => function ($allowed, $meta_key, $post_id, $user_id) {
				return user_can($user_id, 'edit_post', $post_id);
			},
		)
	);
}
add_action('init', 'lpu_register_header_meta');

/**
 * Enqueue the page settings control in the block editor sidebar.
 */
function lpu_enqueue_editor_settings()
{
	// there is no build step (at the moment), therefore must declare the
	// dependencies manually
	wp_enqueue_script(
		'lpu-editor-settings',
		get_theme_file_uri('assets/js/editor-settings.js'),
		array('wp-components', 'wp-data', 'wp-element', 'wp-edit-post', 'wp-plugins'),
		wp_get_theme()->get('Version'),
	);
}
// enqueue_block_editor_assets is meant for block editor UI (not the content)
add_action('enqueue_block_editor_assets', 'lpu_enqueue_editor_settings');

/**
 * Add the transparent state to the page body when explicitly enabled.
 *
 * @param array $classes Body classes.
 * @return array
 */
function lpu_add_header_body_class($classes)
{
	if (lpu_is_transparent_header()) {
		$classes[] = 'lpu-header-transparent';
	}

	return $classes;
}
add_filter('body_class', 'lpu_add_header_body_class');

add_filter('register_block_type_args', function ($args, $name) {
	if ('core/navigation' === $name) {
		// accept navigation to have group as a child
		$args['allowed_blocks'][] = 'core/group';
	}

	if (
		'core/navigation-link' === $name || 'core/navigation-submenu' === $name
	) {
		// they accept group as parent
		$args['parent'][] = 'core/group';
	}

	return $args;
}, 10, 2);

/* messes up the rendered html
 * should be:
 * ul
 * 	group
 * 		li*3
 * 	li
 * 		logo
 * 	group
 * 		li*3
 * 
 * ul
 * 	li
 * 		group
 * 	li*3 items that should be in the group
 * 	li
 * 		logo
 * 	3*li they should be in a group
*/

add_filter('block_core_navigation_listable_blocks', function ($blocks) {
	$blocks[] = 'core/group';
	return $blocks;
});

add_filter('render_block_core/group', function ($block_content, $block) {
	error_log('--- GROUP RENDER ---');
	error_log($block_content);
	return $block_content;
}, 20, 2);

/**
 * Enable SVG file upload
 */
function lpu_allow_svg_upload($mimes)
{
	$mimes['svg'] = 'image/svg+xml';
	return $mimes;
}
add_filter('upload_mimes', 'lpu_allow_svg_upload');

/**
 * WordPress may identify some SVG files as text or xml.
 * Treat SVG files as the official theme logo type in that same CLI-only
 * provisioning context.
 */
function lpu_allow_cli_svg_filetype($data, $file, $filename, $mimes, $real_mime)
{
	$extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

	if ('svg' === $extension) {
		$data['ext']  = 'svg';
		$data['type'] = 'image/svg+xml';
	}

	return $data;
}
add_filter('wp_check_filetype_and_ext', 'lpu_allow_cli_svg_filetype', 10, 5);
