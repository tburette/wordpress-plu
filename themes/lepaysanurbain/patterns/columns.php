<?php
/**
 * Title: Colonnes de texte
 * Slug: lepaysanurbain/columns
 * Categories: lpu-sections
 * Description: Bandeau vert avec titre commun et nombre libre de colonnes de texte ou d’images.
 * Keywords: colonnes, texte, messages, informations
 * Post Types: page
 */
?>
<!-- wp:group {"align":"full","backgroundColor":"vert-fonce","textColor":"ecru","className":"lpu-band lpu-columns-text","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull lpu-band lpu-columns-text has-ecru-color has-vert-fonce-background-color has-text-color has-background">
	<!-- wp:heading {"align":"wide","textAlign":"center","textColor":"ecru","level":2,"fontSize":"subtitle","className":"lpu-columns-text__title"} -->
	<h2 class="wp-block-heading alignwide has-text-align-center lpu-columns-text__title has-ecru-color has-text-color has-subtitle-font-size">Titre commun</h2>
	<!-- /wp:heading -->

	<!-- wp:columns {"align":"wide","className":"lpu-columns-text__columns"} -->
	<div class="wp-block-columns alignwide lpu-columns-text__columns">
		<!-- wp:column {"textColor":"ecru"} -->
		<div class="wp-block-column has-ecru-color has-text-color">
			<!-- wp:paragraph {"textColor":"ecru"} -->
			<p class="has-ecru-color has-text-color">Premier message à présenter dans cette colonne.</p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"textColor":"ecru"} -->
		<div class="wp-block-column has-ecru-color has-text-color">
			<!-- wp:paragraph {"textColor":"ecru"} -->
			<p class="has-ecru-color has-text-color">Deuxième message à présenter dans cette colonne.</p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"textColor":"ecru"} -->
		<div class="wp-block-column has-ecru-color has-text-color">
			<!-- wp:paragraph {"textColor":"ecru"} -->
			<p class="has-ecru-color has-text-color">Troisième message à présenter dans cette colonne.</p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</div>
<!-- /wp:group -->
