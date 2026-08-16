<?php
/**
 * Plugin Name: LPU — Sections côte à côte
 * Description: Prototype indépendant de sections Gutenberg à deux zones pour Le Paysan Urbain.
 * Version: 0.2.0
 * Requires at least: 6.4
 * Requires PHP: 7.4
 * Text Domain: lpu-split-section
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'LPU_SPLIT_SECTION_VERSION', '0.2.0' );

/**
 * Return a stable development version for a local asset.
 *
 * @param string $path Absolute asset path.
 * @return string
 */
function lpu_split_section_asset_version( $path ) {
	$mtime = file_exists( $path ) ? filemtime( $path ) : false;

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
function lpu_split_section_get_frames() {
	$manifest_path = plugin_dir_path( __FILE__ ) . 'frames.json';
	$manifest      = file_exists( $manifest_path ) ? json_decode( file_get_contents( $manifest_path ), true ) : array();
	$frames        = array();

	if ( is_array( $manifest ) ) {
		foreach ( $manifest as $entry ) {
			if ( ! is_array( $entry ) || empty( $entry['slug'] ) || empty( $entry['label'] ) ) {
				continue;
			}

			$slug = sanitize_key( $entry['slug'] );
			if ( '' === $slug ) {
				continue;
			}

			$background = isset( $entry['background'] ) && is_array( $entry['background'] )
				? $entry['background']
				: array( 'type' => 'none' );

			if ( isset( $background['asset'] ) && is_string( $background['asset'] ) ) {
				$background = array(
					'type' => 'image',
					'url'  => get_theme_file_uri( ltrim( $background['asset'], '/' ) ),
				);
			}

			$frames[ $slug ] = array(
				'label'      => __( (string) $entry['label'], 'lpu-split-section' ),
				'available'  => false !== ( $entry['available'] ?? true ),
				'default'    => ! empty( $entry['default'] ),
				'background' => $background,
			);
		}
	}

	$frames = apply_filters( 'lpu_split_section_frames', $frames );
	if ( ! is_array( $frames ) ) {
		$frames = array();
	}

	// `none` is the safe fallback for unknown or retired frame values. Keep it
	// in the catalogue even when a filter omits it, so old content remains
	// valid and new blocks always have a usable fallback.
	if ( ! isset( $frames['none'] ) || ! is_array( $frames['none'] ) ) {
		$frames = array(
			'none' => array(
				'label'      => __( 'Aucun cadre', 'lpu-split-section' ),
				'available'  => true,
				'background' => array( 'type' => 'none' ),
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
function lpu_split_section_editor_frame_options( $frames ) {
	$options = array();

	foreach ( $frames as $name => $frame ) {
		if ( ! is_array( $frame ) || empty( $frame['label'] ) || false === ( $frame['available'] ?? true ) ) {
			continue;
		}

		$options[] = array(
			'label' => (string) $frame['label'],
			'value' => (string) sanitize_key( $name ),
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
function lpu_split_section_frame_values( $frames ) {
	$values = array();

	foreach ( $frames as $name => $frame ) {
		if ( is_array( $frame ) ) {
			$values[] = (string) sanitize_key( $name );
		}
	}

	return array_values( array_unique( array_filter( $values ) ) );
}

/**
 * Pick the default frame for newly inserted sections.
 *
 * @param array<string, array<string, mixed>> $frames Frame catalogue.
 * @return string
 */
function lpu_split_section_default_frame( $frames ) {
	foreach ( $frames as $name => $frame ) {
		if ( is_array( $frame ) && ! empty( $frame['default'] ) && ! empty( $frame['available'] ) ) {
			return (string) sanitize_key( $name );
		}
	}

	foreach ( $frames as $name => $frame ) {
		if ( is_array( $frame ) && ! empty( $frame['available'] ) ) {
			return (string) sanitize_key( $name );
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
function lpu_split_section_frame_declaration( $frame ) {
	if ( ! is_array( $frame ) || empty( $frame['background'] ) || ! is_array( $frame['background'] ) ) {
		return '';
	}

	$background = $frame['background'];
	$type       = isset( $background['type'] ) ? (string) $background['type'] : 'none';

	if ( 'none' === $type ) {
		return 'background: transparent;';
	}

	if ( 'color' === $type && ! empty( $background['value'] ) ) {
		$value = (string) $background['value'];
		if ( preg_match( '/^[a-zA-Z0-9_\\-().%,# ]+$/', $value ) ) {
			return 'background-color: ' . $value . ';';
		}
	}

	if ( 'image' === $type && ! empty( $background['url'] ) ) {
		$url = esc_url_raw( (string) $background['url'] );
		if ( '' !== $url ) {
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
function lpu_split_section_frame_css( $frames ) {
	$css = '';

	foreach ( $frames as $name => $frame ) {
		$class_name = sanitize_html_class( (string) $name );
		$declaration = lpu_split_section_frame_declaration( $frame );

		if ( '' === $class_name || '' === $declaration ) {
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
function lpu_split_section_register_assets() {
	$plugin_path = plugin_dir_path( __FILE__ );
	$plugin_url  = plugin_dir_url( __FILE__ );
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
		lpu_split_section_asset_version( $plugin_path . 'assets/editor.js' ),
		true
	);

	wp_register_style(
		'lpu-split-section',
		$plugin_url . 'assets/style.css',
		array(),
		lpu_split_section_asset_version( $plugin_path . 'assets/style.css' )
	);

	wp_register_style(
		'lpu-split-section-editor',
		$plugin_url . 'assets/editor.css',
		array( 'lpu-split-section' ),
		lpu_split_section_asset_version( $plugin_path . 'assets/editor.css' )
	);

	wp_localize_script(
		'lpu-split-section-editor',
		'lpuSplitSectionConfig',
		array(
			'frames'       => lpu_split_section_editor_frame_options( $frames ),
			'frameValues'  => lpu_split_section_frame_values( $frames ),
			'defaultFrame' => lpu_split_section_default_frame( $frames ),
		)
	);

	// The editor style depends on this handle, so the generated catalogue CSS
	// is loaded in both the front end and the Gutenberg canvas from one source.
	wp_add_inline_style( 'lpu-split-section', lpu_split_section_frame_css( $frames ) );
}
add_action( 'init', 'lpu_split_section_register_assets', 5 );

/**
 * Register the parent block and its two-zone child block.
 *
 * The parent owns the exact two-child contract in editor.js. Each child owns
 * its own InnerBlocks tree and frame attributes, so the two halves remain
 * independent without allowing a third column to be inserted accidentally.
 *
 * @return void
 */
function lpu_split_section_register_blocks() {
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
				'align' => array( 'wide', 'full' ),
				'html'  => false,
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
add_action( 'init', 'lpu_split_section_register_blocks', 10 );

/**
 * Register visual patterns for the custom block implementation.
 *
 * These are intentionally separate from the theme's existing patterns. The
 * plugin can therefore be disabled while the Core-only prototype remains
 * available, or enabled for a direct comparison in the same editor.
 *
 * @return void
 */
function lpu_split_section_register_patterns() {
	$placeholder = esc_url( get_theme_file_uri( 'assets/images/pattern-placeholder.svg' ) );
	$network_logo = esc_url( get_theme_file_uri( 'assets/images/logos/network-horizontal-ecru-baseline.svg' ) );

	$patterns = array(
		'lpu-split-section/split-free'          => array(
			'title'       => 'Côte à côte — deux zones libres (bloc LPU)',
			'description' => 'Deux zones indépendantes avec un cadre différent de chaque côté.',
			'keywords'    => array( 'côte à côte', 'deux zones', 'motif', 'bloc' ),
			'content'     => <<<'HTML'
<!-- wp:lpu/split-section {"align":"full"} -->
<div class="wp-block-lpu-split-section alignfull lpu-split-v2">
	<!-- wp:lpu/split-zone {"side":"left","frame":"ecru","mediaFill":false} -->
	<div class="wp-block-lpu-split-zone lpu-split-v2__zone lpu-split-v2__zone--left lpu-split-v2__zone--frame-ecru">
		<!-- wp:group {"className":"lpu-split-v2__inset","layout":{"type":"constrained"}} -->
		<div class="wp-block-group lpu-split-v2__inset">
			<!-- wp:paragraph {"fontFamily":"oswald","fontSize":"text","className":"lpu-eyebrow"} -->
			<p class="lpu-eyebrow has-oswald-font-family has-text-font-size">Sur-titre</p>
			<!-- /wp:paragraph -->

			<!-- wp:heading {"level":2,"fontSize":"title"} -->
			<h2 class="wp-block-heading has-title-font-size">Titre de la zone gauche</h2>
			<!-- /wp:heading -->

			<!-- wp:paragraph -->
			<p>Ajoutez ici tous les blocs propres à cette moitié : titre, texte, liste, image ou bouton.</p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:lpu/split-zone -->

	<!-- wp:lpu/split-zone {"side":"right","frame":"motif-4","mediaFill":false} -->
	<div class="wp-block-lpu-split-zone lpu-split-v2__zone lpu-split-v2__zone--right lpu-split-v2__zone--frame-motif-4">
		<!-- wp:group {"backgroundColor":"ecru","className":"lpu-split-v2__inset","layout":{"type":"constrained"}} -->
		<div class="wp-block-group lpu-split-v2__inset has-ecru-background-color has-background">
			<!-- wp:paragraph {"fontFamily":"oswald","fontSize":"text","className":"lpu-eyebrow"} -->
			<p class="lpu-eyebrow has-oswald-font-family has-text-font-size">Zone droite</p>
			<!-- /wp:paragraph -->

			<!-- wp:heading {"level":2,"fontSize":"subtitle"} -->
			<h2 class="wp-block-heading has-subtitle-font-size">Un autre contenu indépendant</h2>
			<!-- /wp:heading -->
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:lpu/split-zone -->
</div>
<!-- /wp:lpu/split-section -->
HTML,
		),
		'lpu-split-section/split-content-image' => array(
			'title'       => 'Côte à côte — titre, texte et image (bloc LPU)',
			'description' => 'Contenu éditorial indépendant à gauche et image pleine zone à droite.',
			'keywords'    => array( 'titre', 'texte', 'image', 'côte à côte', 'bloc' ),
			'content'     => <<<'HTML'
<!-- wp:lpu/split-section {"align":"full"} -->
<div class="wp-block-lpu-split-section alignfull lpu-split-v2">
	<!-- wp:lpu/split-zone {"side":"left","frame":"ecru","mediaFill":false} -->
	<div class="wp-block-lpu-split-zone lpu-split-v2__zone lpu-split-v2__zone--left lpu-split-v2__zone--frame-ecru">
		<!-- wp:paragraph {"fontFamily":"oswald","fontSize":"text","className":"lpu-eyebrow"} -->
		<p class="lpu-eyebrow has-oswald-font-family has-text-font-size">Sur-titre</p>
		<!-- /wp:paragraph -->

		<!-- wp:heading {"level":2,"fontSize":"title"} -->
		<h2 class="wp-block-heading has-title-font-size">Un titre qui tient dans sa moitié</h2>
		<!-- /wp:heading -->

		<!-- wp:paragraph -->
		<p>Ajoutez ici le texte, les informations et les appels à l’action propres à cette zone.</p>
		<!-- /wp:paragraph -->

		<!-- wp:buttons -->
		<div class="wp-block-buttons">
			<!-- wp:button -->
			<div class="wp-block-button"><a class="wp-block-button__link wp-element-button">En savoir plus</a></div>
			<!-- /wp:button -->
		</div>
		<!-- /wp:buttons -->
	</div>
	<!-- /wp:lpu/split-zone -->

	<!-- wp:lpu/split-zone {"side":"right","frame":"none","mediaFill":true} -->
	<div class="wp-block-lpu-split-zone lpu-split-v2__zone lpu-split-v2__zone--right lpu-split-v2__zone--frame-none lpu-split-v2__zone--media-fill">
		<!-- wp:image {"url":"{{PLACEHOLDER}}","alt":"","className":"lpu-media-placeholder","linkDestination":"none"} -->
		<figure class="wp-block-image lpu-media-placeholder"><img src="{{PLACEHOLDER}}" alt="" /></figure>
		<!-- /wp:image -->
	</div>
	<!-- /wp:lpu/split-zone -->
</div>
<!-- /wp:lpu/split-section -->
HTML,
		),
		'lpu-split-section/split-motif-image'   => array(
			'title'       => 'Côte à côte — motif et image (bloc LPU)',
			'description' => 'Cadre motif et contenu éditorial à gauche, image pleine zone à droite.',
			'keywords'    => array( 'motif', 'image', 'mise en avant', 'côte à côte', 'bloc' ),
			'content'     => <<<'HTML'
<!-- wp:lpu/split-section {"align":"full"} -->
<div class="wp-block-lpu-split-section alignfull lpu-split-v2">
	<!-- wp:lpu/split-zone {"side":"left","frame":"motif-7","mediaFill":false} -->
	<div class="wp-block-lpu-split-zone lpu-split-v2__zone lpu-split-v2__zone--left lpu-split-v2__zone--frame-motif-7">
		<!-- wp:group {"backgroundColor":"ecru","className":"lpu-split-v2__inset","layout":{"type":"constrained"}} -->
		<div class="wp-block-group lpu-split-v2__inset has-ecru-background-color has-background">
			<!-- wp:paragraph {"fontFamily":"oswald","fontSize":"text","className":"lpu-eyebrow"} -->
			<p class="lpu-eyebrow has-oswald-font-family has-text-font-size">Une ferme, des savoir-faire</p>
			<!-- /wp:paragraph -->

			<!-- wp:heading {"level":2,"fontSize":"subtitle"} -->
			<h2 class="wp-block-heading has-subtitle-font-size">Cultiver et transmettre au quotidien</h2>
			<!-- /wp:heading -->

			<!-- wp:paragraph -->
			<p>Le panneau intérieur peut accueillir librement le contenu éditorial de cette moitié.</p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:lpu/split-zone -->

	<!-- wp:lpu/split-zone {"side":"right","frame":"none","mediaFill":true} -->
	<div class="wp-block-lpu-split-zone lpu-split-v2__zone lpu-split-v2__zone--right lpu-split-v2__zone--frame-none lpu-split-v2__zone--media-fill">
		<!-- wp:image {"url":"{{PLACEHOLDER}}","alt":"","className":"lpu-media-placeholder","linkDestination":"none"} -->
		<figure class="wp-block-image lpu-media-placeholder"><img src="{{PLACEHOLDER}}" alt="" /></figure>
		<!-- /wp:image -->
	</div>
	<!-- /wp:lpu/split-zone -->
</div>
<!-- /wp:lpu/split-section -->
HTML,
		),
		'lpu-split-section/split-logo-content'   => array(
			'title'       => 'Côte à côte — logo et titre-texte (bloc LPU)',
			'description' => 'Identité visuelle à gauche et contenu éditorial indépendant à droite.',
			'keywords'    => array( 'logo', 'titre', 'texte', 'côte à côte', 'bloc' ),
			'content'     => <<<'HTML'
<!-- wp:lpu/split-section {"align":"full"} -->
<div class="wp-block-lpu-split-section alignfull lpu-split-v2">
	<!-- wp:lpu/split-zone {"side":"left","frame":"green","mediaFill":false} -->
	<div class="wp-block-lpu-split-zone lpu-split-v2__zone lpu-split-v2__zone--left lpu-split-v2__zone--frame-green">
		<!-- wp:image {"url":"{{NETWORK_LOGO}}","alt":"Le Paysan Urbain","className":"lpu-split-v2__logo","linkDestination":"none"} -->
		<figure class="wp-block-image lpu-split-v2__logo"><img src="{{NETWORK_LOGO}}" alt="Le Paysan Urbain" /></figure>
		<!-- /wp:image -->
	</div>
	<!-- /wp:lpu/split-zone -->

	<!-- wp:lpu/split-zone {"side":"right","frame":"ecru","mediaFill":false} -->
	<div class="wp-block-lpu-split-zone lpu-split-v2__zone lpu-split-v2__zone--right lpu-split-v2__zone--frame-ecru">
		<!-- wp:paragraph {"fontFamily":"oswald","fontSize":"text","className":"lpu-eyebrow"} -->
		<p class="lpu-eyebrow has-oswald-font-family has-text-font-size">Qui sommes-nous&nbsp;?</p>
		<!-- /wp:paragraph -->

		<!-- wp:heading {"level":2,"fontSize":"subtitle"} -->
		<h2 class="wp-block-heading has-subtitle-font-size">Un titre et un texte dans l’autre moitié</h2>
		<!-- /wp:heading -->

		<!-- wp:paragraph -->
		<p>Cette zone reste indépendante : réorganisez ses blocs sans toucher à la moitié gauche.</p>
		<!-- /wp:paragraph -->

		<!-- wp:buttons -->
		<div class="wp-block-buttons">
			<!-- wp:button -->
			<div class="wp-block-button"><a class="wp-block-button__link wp-element-button">Découvrir</a></div>
			<!-- /wp:button -->
		</div>
		<!-- /wp:buttons -->
	</div>
	<!-- /wp:lpu/split-zone -->
</div>
<!-- /wp:lpu/split-section -->
HTML,
		),
	);

	foreach ( $patterns as $name => $pattern ) {
		$pattern['categories'] = array( 'lpu-sections' );
		$pattern['content']    = str_replace(
			array( '{{PLACEHOLDER}}', '{{NETWORK_LOGO}}' ),
			array( $placeholder, $network_logo ),
			$pattern['content']
		);

		register_block_pattern( $name, $pattern );
	}
}
add_action( 'init', 'lpu_split_section_register_patterns', 20 );
