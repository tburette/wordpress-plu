<?php

/**
 * Plugin Name: LPU — Sections côte à côte
 * Description: Blocs Gutenberg de sections côte à côte à deux zones pour Le Paysan Urbain.
 * Version: 0.3.0
 * Requires at least: 7.1
 * Requires PHP: 7.4
 * Text Domain: lpu-split-section
 */

if (! defined('ABSPATH')) {
	exit;
}

define('LPU_SPLIT_SECTION_VERSION', '0.3.0');

/**
 * Return a URL for an asset belonging to the LPU theme.
 *
 * This plugin is loaded before provisioning may activate the theme in the
 * current request. Using get_theme_file_uri() here would therefore resolve
 * against whatever theme was active at bootstrap (often Twenty Twenty-Five),
 * leaving saved pattern content with a broken asset URL.
 *
 * @param string $path Relative path inside the LPU theme.
 * @return string
 */
function lpu_split_section_theme_asset_uri($path)
{
	$path  = ltrim((string) $path, '/');
	$theme = wp_get_theme('lepaysanurbain');

	if ($theme->exists()) {
		return trailingslashit($theme->get_stylesheet_directory_uri()) . $path;
	}

	// Keep the plugin usable while the companion theme is being installed. The
	// provisioning dependency check still reports a missing LPU theme clearly.
	return get_theme_file_uri($path);
}

/**
 * Return a stable development version for a local asset.
 *
 * @param string $path Absolute asset path.
 * @return string
 */
function lpu_split_section_asset_version($path)
{
	$mtime = file_exists($path) ? filemtime($path) : false;

	return $mtime ? (string) $mtime : LPU_SPLIT_SECTION_VERSION;
}

/**
 * Return the normalized frame catalogue shared by the editor and front end.
 *
 * The built-in catalogue lives in frames.json so adding or retiring a frame
 * does not require editing JavaScript or CSS. A companion theme/plugin can
 * extend or alter the catalogue through the filter. Keep a retired frame in
 * the catalogue with `available: false` until saved content using it has been
 * migrated; it will still receive its front-end CSS but will not be offered
 * in the Inspector Control.
 *
 * Frame contract:
 * - `label`: human-readable option label;
 * - `available`: whether the frame is offered to new edits;
 * - `background.type`: `none`, `color`, or `image`;
 * - `background.value`: a CSS color/token for `color`;
 * - `background.url`: an absolute image URL for `image`.
 *
 * @return array<string, array<string, mixed>>
 */
function lpu_split_section_get_frames()
{
	$manifest_path = plugin_dir_path(__FILE__) . 'frames.json';
	$manifest      = file_exists($manifest_path) ? json_decode(file_get_contents($manifest_path), true) : array();
	$frames        = array();

	if (is_array($manifest)) {
		foreach ($manifest as $entry) {
			if (! is_array($entry) || empty($entry['slug']) || empty($entry['label'])) {
				continue;
			}

			$slug = sanitize_key($entry['slug']);
			if ('' === $slug) {
				continue;
			}

			$background = isset($entry['background']) && is_array($entry['background'])
				? $entry['background']
				: array('type' => 'none');

			if (isset($background['asset']) && is_string($background['asset'])) {
				$background = array(
					'type' => 'image',
					'url'  => lpu_split_section_theme_asset_uri($background['asset']),
				);
			}

			$frames[$slug] = array(
				'label'      => __((string) $entry['label'], 'lpu-split-section'),
				'available'  => false !== ($entry['available'] ?? true),
				'default'    => ! empty($entry['default']),
				'background' => $background,
			);
		}
	}

	$frames = apply_filters('lpu_split_section_frames', $frames);
	if (! is_array($frames)) {
		$frames = array();
	}

	// `none` is the safe fallback for unknown or retired frame values. Keep it
	// in the catalogue even when a filter omits it, so old content remains
	// valid and new blocks always have a usable fallback.
	if (! isset($frames['none']) || ! is_array($frames['none'])) {
		$frames = array(
			'none' => array(
				'label'      => __('Aucun cadre', 'lpu-split-section'),
				'available'  => true,
				'background' => array('type' => 'none'),
			),
		) + $frames;
	}

	return $frames;
}

/**
 * Convert the frame catalogue to Inspector Control options.
 *
 * @param array<string, array<string, mixed>> $frames Frame catalogue.
 * @return array<int, array<string, string>>
 */
function lpu_split_section_editor_frame_options($frames)
{
	$options = array();

	foreach ($frames as $name => $frame) {
		if (! is_array($frame) || empty($frame['label']) || false === ($frame['available'] ?? true)) {
			continue;
		}

		$options[] = array(
			'label' => (string) $frame['label'],
			'value' => (string) sanitize_key($name),
		);
	}

	return $options;
}

/**
 * Return all frame keys, including retired keys retained for compatibility.
 *
 * @param array<string, array<string, mixed>> $frames Frame catalogue.
 * @return array<int, string>
 */
function lpu_split_section_frame_values($frames)
{
	$values = array();

	foreach ($frames as $name => $frame) {
		if (is_array($frame)) {
			$values[] = (string) sanitize_key($name);
		}
	}

	return array_values(array_unique(array_filter($values)));
}

/**
 * Pick the default frame for newly inserted sections.
 *
 * @param array<string, array<string, mixed>> $frames Frame catalogue.
 * @return string
 */
function lpu_split_section_default_frame($frames)
{
	foreach ($frames as $name => $frame) {
		if (is_array($frame) && ! empty($frame['default']) && ! empty($frame['available'])) {
			return (string) sanitize_key($name);
		}
	}

	foreach ($frames as $name => $frame) {
		if (is_array($frame) && ! empty($frame['available'])) {
			return (string) sanitize_key($name);
		}
	}

	return 'none';
}

/**
 * Build a CSS declaration from the structured frame background contract.
 *
 * @param array<string, mixed> $frame Frame definition.
 * @return string
 */
function lpu_split_section_frame_declaration($frame)
{
	if (! is_array($frame) || empty($frame['background']) || ! is_array($frame['background'])) {
		return '';
	}

	$background = $frame['background'];
	$type       = isset($background['type']) ? (string) $background['type'] : 'none';

	if ('none' === $type) {
		return 'background: transparent;';
	}

	if ('color' === $type && ! empty($background['value'])) {
		$value = (string) $background['value'];
		if (preg_match('/^[a-zA-Z0-9_\\-().%,# ]+$/', $value)) {
			return 'background-color: ' . $value . ';';
		}
	}

	if ('image' === $type && ! empty($background['url'])) {
		$url = esc_url_raw((string) $background['url']);
		if ('' !== $url) {
			return 'background: url("' . $url . '") center / cover no-repeat;';
		}
	}

	return '';
}

/**
 * Generate the frame classes used by saved block markup.
 *
 * @param array<string, array<string, mixed>> $frames Frame catalogue.
 * @return string
 */
function lpu_split_section_frame_css($frames)
{
	$css = '';

	foreach ($frames as $name => $frame) {
		$class_name = sanitize_html_class((string) $name);
		$declaration = lpu_split_section_frame_declaration($frame);

		if ('' === $class_name || '' === $declaration) {
			continue;
		}

		$css .= sprintf(
			".lpu-split-v2__zone--frame-%1\$s {%2\$s}\n",
			$class_name,
			$declaration
		);
	}

	return $css;
}

/**
 * Register the no-build editor assets used by both blocks.
 *
 * @return void
 */
function lpu_split_section_register_assets()
{
	$plugin_path = plugin_dir_path(__FILE__);
	$plugin_url  = plugin_dir_url(__FILE__);
	$frames      = lpu_split_section_get_frames();

	wp_register_script(
		'lpu-split-section-editor',
		$plugin_url . 'assets/editor.js',
		array(
			'wp-blocks',
			'wp-element',
			'wp-block-editor',
			'wp-components',
			'wp-data',
			'wp-i18n',
		),
		lpu_split_section_asset_version($plugin_path . 'assets/editor.js'),
		true
	);

	wp_register_style(
		'lpu-split-section',
		$plugin_url . 'assets/style.css',
		array(),
		lpu_split_section_asset_version($plugin_path . 'assets/style.css')
	);

	wp_register_style(
		'lpu-split-section-editor',
		$plugin_url . 'assets/editor.css',
		array('lpu-split-section'),
		lpu_split_section_asset_version($plugin_path . 'assets/editor.css')
	);

	wp_localize_script(
		'lpu-split-section-editor',
		'lpuSplitSectionConfig',
		array(
			'frames'       => lpu_split_section_editor_frame_options($frames),
			'frameValues'  => lpu_split_section_frame_values($frames),
			'defaultFrame' => lpu_split_section_default_frame($frames),
		)
	);

	// The editor style depends on this handle, so the generated catalogue CSS
	// is loaded in both the front end and the Gutenberg canvas from one source.
	wp_add_inline_style('lpu-split-section', lpu_split_section_frame_css($frames));
}
add_action('init', 'lpu_split_section_register_assets', 5);

/**
 * Register the parent block and its two-zone child block.
 *
 * The parent owns the exact two-child contract in editor.js. Each child owns
 * its own InnerBlocks tree and frame attributes, so the two halves remain
 * independent without allowing a third column to be inserted accidentally.
 *
 * @return void
 */
function lpu_split_section_register_blocks()
{
	register_block_type(
		'lpu/split-section',
		array(
			'api_version'    => 3,
			'title'          => 'Section côte à côte',
			'description'    => 'Une section en deux zones exactement, chacune avec son propre contenu.',
			'category'       => 'design',
			'icon'           => 'columns',
			'editor_script'  => 'lpu-split-section-editor',
			'style'          => 'lpu-split-section',
			'editor_style'   => 'lpu-split-section-editor',
			'supports'       => array(
				'align'   => array('wide', 'full'),
				'html'    => false,
				'spacing' => array(
					'margin' => array('top', 'bottom'),
				),
			),
		)
	);

	register_block_type(
		'lpu/split-zone',
		array(
			'api_version'   => 3,
			'title'         => 'Zone de section côte à côte',
			'description'   => 'Une moitié indépendante de la section côte à côte.',
			'category'      => 'design',
			'icon'          => 'align-wide',
			'parent'        => array('lpu/split-section'),
			'editor_script' => 'lpu-split-section-editor',
			'style'         => 'lpu-split-section',
			'editor_style'  => 'lpu-split-section-editor',
			'attributes'    => array(
				'side'      => array(
					'type'    => 'string',
					'default' => 'left',
				),
				'frame'     => array(
					'type'    => 'string',
					'default' => 'ecru',
				),
				'mediaFill' => array(
					'type'    => 'boolean',
					'default' => false,
				),
				'mediaId'   => array(
					'type'    => 'number',
					'default' => 0,
				),
				'mediaUrl'  => array(
					'type'    => 'string',
					'default' => '',
				),
			),
			'supports'      => array(
				'html'      => false,
				'className' => false,
			),
		)
	);
}
add_action('init', 'lpu_split_section_register_blocks', 10);

/**
 * Register visual patterns for the custom block implementation.
 *
 * The block markup lives in one file per pattern. WordPress 7.1 loads each
 * file through the registry when its content is requested.
 *
 * @return void
 */
function lpu_split_section_register_patterns()
{
	$pattern_dir = plugin_dir_path(__FILE__) . 'patterns/';
	$patterns    = array(
		'lpu-split-section/split-free' => array(
			'title'       => 'Côte à côte — deux zones libres (bloc LPU)',
			'description' => 'Deux zones indépendantes avec un cadre différent de chaque côté.',
			'keywords'    => array('côte à côte', 'deux zones', 'motif', 'bloc'),
			'filePath'    => $pattern_dir . 'split-free.php',
		),
		'lpu-split-section/split-content-image' => array(
			'title'       => 'Côte à côte — titre, texte et image (bloc LPU)',
			'description' => 'Contenu éditorial indépendant à gauche et image pleine zone à droite.',
			'keywords'    => array('titre', 'texte', 'image', 'côte à côte', 'bloc'),
			'filePath'    => $pattern_dir . 'split-content-image.php',
		),
		'lpu-split-section/split-motif-image' => array(
			'title'       => 'Côte à côte — motif et image (bloc LPU)',
			'description' => 'Cadre motif et contenu éditorial à gauche, image pleine zone à droite.',
			'keywords'    => array('motif', 'image', 'mise en avant', 'côte à côte', 'bloc'),
			'filePath'    => $pattern_dir . 'split-motif-image.php',
		),
		'lpu-split-section/split-logo-content' => array(
			'title'       => 'Côte à côte — logo et titre-texte (bloc LPU)',
			'description' => 'Identité visuelle à gauche et contenu éditorial indépendant à droite.',
			'keywords'    => array('logo', 'titre', 'texte', 'côte à côte', 'bloc'),
			'filePath'    => $pattern_dir . 'split-logo-content.php',
		),
	);

	foreach ($patterns as $name => $pattern) {
		$pattern['categories'] = array('lpu-sections');
		$pattern['source']     = 'plugin';
		register_block_pattern(
			$name,
			$pattern
		);
	}
}

/*
 * The pattern registry is request-scoped, so registration remains on init
 * for every request that exposes the patterns.
 */
add_action('init', 'lpu_split_section_register_patterns', 20);
