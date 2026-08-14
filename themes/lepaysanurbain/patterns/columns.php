<?php
/**
 * Title: Colonnes de texte — message commun
 * Slug: lepaysanurbain/columns
 * Categories: lpu-sections
 * Description: Bandeau vert avec titre commun et plusieurs colonnes de texte ou d’images.
 * Keywords: colonnes, texte, messages, informations
 * Post Types: page
 */
?>
<!-- wp:group {"align":"full","backgroundColor":"vert-fonce","textColor":"ecru","className":"lpu-band lpu-columns-text","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull lpu-band lpu-columns-text has-ecru-color has-vert-fonce-background-color has-text-color has-background">
	<!-- wp:heading {"align":"wide","textAlign":"center","textColor":"ecru","level":2,"fontSize":"subtitle","className":"lpu-columns-text__title"} -->
	<h2 class="wp-block-heading alignwide has-text-align-center lpu-columns-text__title has-ecru-color has-text-color has-subtitle-font-size">Un message commun à faire vivre</h2>
	<!-- /wp:heading -->

	<!-- wp:columns {"align":"wide","className":"lpu-columns-text__columns"} -->
	<div class="wp-block-columns alignwide lpu-columns-text__columns">
		<!-- wp:column {"textColor":"ecru"} -->
		<div class="wp-block-column has-ecru-color has-text-color">
			<!-- wp:paragraph {"textColor":"ecru"} -->
			<p class="has-ecru-color has-text-color">Présentez ici un premier message court, une information ou une valeur importante du projet.</p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"textColor":"ecru"} -->
		<div class="wp-block-column has-ecru-color has-text-color">
			<!-- wp:paragraph {"textColor":"ecru"} -->
			<p class="has-ecru-color has-text-color">Utilisez cette colonne pour compléter le propos avec un deuxième message lisible et autonome.</p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"textColor":"ecru"} -->
		<div class="wp-block-column has-ecru-color has-text-color">
			<!-- wp:paragraph {"textColor":"ecru"} -->
			<p class="has-ecru-color has-text-color">Ajoutez un dernier repère, un chiffre ou un lien vers une information complémentaire.</p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</div>
<!-- /wp:group -->
