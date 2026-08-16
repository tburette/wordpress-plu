<?php
/**
 * Title: Côte à côte — deux zones libres (Core)
 * Slug: lepaysanurbain/split-free
 * Categories: lpu-sections
 * Description: Deux colonnes exactement, éditables séparément avec les blocs Core et un cadre propre à chaque côté.
 * Keywords: côte à côte, deux colonnes, libre, Core
 * Post Types: page
 */
?>
<!-- wp:group {"align":"full","className":"lpu-split-v1","layout":{"type":"default"}} -->
<div class="wp-block-group alignfull lpu-split-v1">
	<!-- wp:columns {"align":"full","className":"lpu-split-v1__columns"} -->
	<div class="wp-block-columns alignfull lpu-split-v1__columns">
		<!-- wp:column {"width":"50%","className":"lpu-split-v1__column"} -->
		<div class="wp-block-column lpu-split-v1__column" style="flex-basis:50%">
			<!-- wp:group {"className":"lpu-split-v1__side is-style-lpu-split-frame-ecru","layout":{"type":"constrained"},"metadata":{"name":"Côté gauche — zone indépendante"}} -->
			<div class="wp-block-group lpu-split-v1__side is-style-lpu-split-frame-ecru">
				<!-- wp:heading {"level":2,"fontSize":"subtitle"} -->
				<h2 class="wp-block-heading has-subtitle-font-size">Titre de la zone gauche</h2>
				<!-- /wp:heading -->

				<!-- wp:paragraph -->
				<p>Remplacez ces blocs par le contenu souhaité : sous-titre, paragraphe, liste, image ou bouton.</p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"width":"50%","className":"lpu-split-v1__column"} -->
		<div class="wp-block-column lpu-split-v1__column" style="flex-basis:50%">
			<!-- wp:group {"className":"lpu-split-v1__side is-style-lpu-split-frame-motif-4","layout":{"type":"constrained"},"metadata":{"name":"Côté droit — zone indépendante"}} -->
			<div class="wp-block-group lpu-split-v1__side is-style-lpu-split-frame-motif-4">
				<!-- wp:group {"backgroundColor":"ecru","className":"lpu-split-v1__inset","layout":{"type":"constrained"}} -->
				<div class="wp-block-group lpu-split-v1__inset has-ecru-background-color has-background">
					<!-- wp:paragraph {"fontFamily":"oswald","fontSize":"text","className":"lpu-eyebrow"} -->
					<p class="lpu-eyebrow has-oswald-font-family has-text-font-size">Zone droite</p>
					<!-- /wp:paragraph -->

					<!-- wp:heading {"level":2,"fontSize":"subtitle"} -->
					<h2 class="wp-block-heading has-subtitle-font-size">Un autre contenu indépendant</h2>
					<!-- /wp:heading -->
				</div>
				<!-- /wp:group -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</div>
<!-- /wp:group -->
