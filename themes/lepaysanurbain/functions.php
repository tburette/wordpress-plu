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
 * Can activate contentPnly mode for individual unsynced patterns by using 
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
 * Enqueue the frontend js code for the menu (header)
 */
function lpu_enqueue_navigation_script()
{
	$theme = wp_get_theme();

	wp_enqueue_script(
		'lpu-navigation',
		get_theme_file_uri('assets/js/navigation.js'),
		array(),
		$theme->get('Version'),
		true
	);
}
add_action('wp_enqueue_scripts', 'lpu_enqueue_navigation_script');

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

/**
 * Count the direct navigation items in a serialized Navigation post.
 *
 * @param string $navigation_content Serialized Navigation block content.
 * @return int
 */
function lpu_get_navigation_item_count($navigation_content)
{
	$count = 0;

	foreach (parse_blocks($navigation_content) as $block) {
		if (
			in_array(
				$block['blockName'] ?? '',
				array('core/navigation-link', 'core/navigation-submenu'),
				true
			)
		) {
			$count++;
		}
	}

	return $count;
}

/**
 * Bind a Navigation block to the correct navigation element (the one set for
 * the current site). Navigation "posts" (the things holding all data of a
 * navigation elements) are site-local in a multisite network.
 * We can't just create a template part, say, a header or footer, and assign it
 * a fixed navigation "post". The reason is that the template part is shared.
 * The header and footer use separate site-local Navigation posts, identified
 * by their class names.
 * There is a detection mechanism to only change navigations that are using this
 * mechanism, other Navigation blocks are intentionally left untouched so inline
 * content navigations cannot be hijacked by the shared menus.
 *
 * @param array         $parsed_block The block being prepared for rendering.
 * @param array         $source_block The original parsed block.
 * @param WP_Block|null $parent_block The parent block, if any.
 * @return array
 */
function lpu_bind_site_navigation($parsed_block, $source_block, $parent_block)
{
	if ('core/navigation' !== ($parsed_block['blockName'] ?? '')) {
		return $parsed_block;
	}

	if (isset($parsed_block['attrs']['ref'])) {
		return $parsed_block;
	}

	$classes = preg_split(
		'/\s+/',
		trim((string) ($parsed_block['attrs']['className'] ?? ''))
	);
	$is_footer_navigation = in_array('lpu-footer-navigation', $classes, true);
	$is_header_navigation = in_array('lpu-header__navigation', $classes, true);

	if (! $is_footer_navigation && ! $is_header_navigation) {
		return $parsed_block;
	}

	$option_name   = $is_footer_navigation ? 'lpu_footer_navigation_id' : 'lpu_navigation_id';
	$navigation_id = absint(get_option($option_name, 0));
	if (! $navigation_id) {
		return $parsed_block;
	}

	$navigation = get_post($navigation_id);
	if (! $navigation || 'wp_navigation' !== $navigation->post_type || 'publish' !== $navigation->post_status) {
		return $parsed_block;
	}

	$parsed_block['attrs']['ref'] = $navigation_id;

	if ($is_header_navigation) {
		$top_level_item_count = lpu_get_navigation_item_count($navigation->post_content);

		if (! in_array($top_level_item_count, array(3, 5), true)) {
			$parsed_block['attrs']['className'] = trim(
				(string) ($parsed_block['attrs']['className'] ?? '')
					. ' lpu-navigation--unsupported-count'
			);
		}
	}

	return $parsed_block;
}
add_filter('render_block_data', 'lpu_bind_site_navigation', 10, 3);
