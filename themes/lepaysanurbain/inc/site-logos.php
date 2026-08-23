<?php

/**
 * Site logos.
 *
 * Two per-site logo settings exist:
 *
 * - `custom_logo`          : the normal (opaque header) logo, WordPress core.
 * - `lpu_transparent_logo` : the écru logo used while the header is
 *                            transparent, a theme mod holding a
 *                            media-library attachment ID.
 *
 * Both images are rendered inside the header's Site Logo link (see the
 * render filter below) and CSS decides which one is visible. The theme has
 * no knowledge of site identity: every site configures its own pair of
 * logos, exactly like any other WordPress site.
 *
 * @package Le_Paysan_Urbain
 */

if (! defined('ABSPATH')) {
	exit;
}

/**
 * Declare custom-logo support so the core Logo picker shows in the
 * Customizer (the custom_logo setting is gated on this theme support).
 *
 * Flexible dimensions skip the crop step, which suits the official SVG
 * logos.
 *
 * @return void
 */
function lpu_setup_custom_logo()
{
	// custom-logo is correct, custom-header is not.
	// custom-header is for background image across the header (with text above)
	add_theme_support(
		'custom-logo',
		array(
			// support flexible height and width.
			// not sure what it does. Copied from the twentyxxx themes
			'flex-width'  => true,
			'flex-height' => true,
		)
	);
}
add_action('after_setup_theme', 'lpu_setup_custom_logo');

/**
 * Register the transparent-header logo setting and control.
 *
 * The control sits in Site Identity, immediately below the core Logo
 * control (core Logo uses priority 8, Site Title/Tagline default to 10).
 * Registering a Customizer control is also what makes the
 * Appearance → Customize menu link appear for a block theme.
 *
 * @param WP_Customize_Manager $wp_customize Customizer manager.
 * @return void
 */
function lpu_customize_register_transparent_logo($wp_customize)
{
	$wp_customize->add_setting(
		'lpu_transparent_logo',
		array(
			'type'              => 'theme_mod',
			'default'           => 0,
			'sanitize_callback' => 'absint',
		)
	);

	$wp_customize->add_control(
		new WP_Customize_Media_Control(
			$wp_customize,
			'lpu_transparent_logo',
			array(
				'section'     => 'title_tagline',
				'priority'    => 9,
				'mime_type'   => 'image',
				'label'       => 'Logo header transparent',
				'description' => 'Affiché à la place du logo quand le header est transparent (pages avec hero).',
			)
		)
	);
}
add_action('customize_register', 'lpu_customize_register_transparent_logo');

/**
 * Make both images in the header logo decorative.
 *
 * The Site Logo block supplies an alt value for the normal logo by default.
 * The header deliberately treats the logo as decorative, so clear the alt
 * value on every image rendered by this specific Site Logo instance.
 *
 * @param string $block_content Rendered block HTML.
 * @return string
 */
function lpu_make_header_logos_decorative($block_content)
{
	if (! class_exists('WP_HTML_Tag_Processor')) {
		return $block_content;
	}

	$processor = new WP_HTML_Tag_Processor($block_content);

	while ($processor->next_tag('img')) {
		$processor->set_attribute('alt', '');
	}

	return $processor->get_updated_html();
}

/**
 * Append the transparent logo image to the header's Site Logo output.
 *
 * Runs only for the Site Logo instance identified by its `lpu-header__logo`
 * class in parts/header.html. The normal image is the untouched core
 * output; the transparent variant is appended inside the same home link and
 * CSS decides which image is visible.
 *
 * @param string $block_content Rendered block HTML.
 * @param array  $parsed_block  Parsed block.
 * @return string
 */
function lpu_render_header_transparent_logo($block_content, $parsed_block)
{
	$classes = preg_split(
		'/\s+/',
		trim((string) ($parsed_block['attrs']['className'] ?? ''))
	);

	if (! in_array('lpu-header__logo', $classes, true)) {
		return $block_content;
	}

	$block_content = lpu_make_header_logos_decorative($block_content);

	// Pages without the transparent-header setting carry the normal logo only.
	if (! lpu_is_transparent_header()) {
		return $block_content;
	}

	$transparent_logo_id = absint(get_theme_mod('lpu_transparent_logo'));

	if (! $transparent_logo_id) {
		// Safe fallback: the normal logo remains the only image.
		if (WP_DEBUG) {
			wp_trigger_error(
				__FUNCTION__,
				'Transparent header enabled for this page but no transparent logo is configured for this site. Falling back to the normal logo.',
				E_USER_WARNING
			);
		}

		return $block_content;
	}

	$transparent_img = wp_get_attachment_image(
		$transparent_logo_id,
		'full',
		false,
		array(
			'class' => 'lpu-logo-transparent',
			// Decorative duplicate: the link's accessible name comes from the normal logo.
			'alt'   => '',
		)
	);

	if ('' === $transparent_img) {
		return $block_content;
	}

	// The header logo block links to the home page; an unlinked Site Logo
	// variant is intentionally left untouched.
	if (false === strpos($block_content, '</a>')) {
		return $block_content;
	}

	// A Site Logo block contains exactly one home link.
	return str_replace('</a>', $transparent_img . '</a>', $block_content);
}
add_filter('render_block_core/site-logo', 'lpu_render_header_transparent_logo', 10, 2);
