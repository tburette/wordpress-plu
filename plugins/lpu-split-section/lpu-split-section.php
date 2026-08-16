<?php
/**
 * Plugin Name: LPU — Sections côte à côte
 * Description: Prototype indépendant de sections Gutenberg à deux zones pour Le Paysan Urbain.
 * Version: 0.1.0
 * Requires at least: 6.4
 * Requires PHP: 7.4
 * Text Domain: lpu-split-section
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'LPU_SPLIT_SECTION_VERSION', '0.1.0' );

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
 * Register the no-build editor assets used by both blocks.
 *
 * @return void
 */
function lpu_split_section_register_assets() {
	$plugin_path = plugin_dir_path( __FILE__ );
	$plugin_url  = plugin_dir_url( __FILE__ );

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
