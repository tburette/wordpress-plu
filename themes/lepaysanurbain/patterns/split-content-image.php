<?php
/**
 * Title: Côte à côte — titre, texte et image (Core)
 * Slug: lepaysanurbain/split-content-image
 * Categories: lpu-sections
 * Description: Deux moitiés indépendantes : contenu éditorial à gauche et image pleine zone à droite.
 * Keywords: côte à côte, titre, texte, image, Core
 * Post Types: page
 */
?>
<!-- wp:group {"align":"full","className":"lpu-split-v1 lpu-split-v1--content-image","layout":{"type":"default"}} -->
<div class="wp-block-group alignfull lpu-split-v1 lpu-split-v1--content-image">
	<!-- wp:columns {"align":"full","className":"lpu-split-v1__columns"} -->
	<div class="wp-block-columns alignfull lpu-split-v1__columns">
		<!-- wp:column {"width":"50%","className":"lpu-split-v1__column"} -->
		<div class="wp-block-column lpu-split-v1__column" style="flex-basis:50%">
			<!-- wp:group {"className":"lpu-split-v1__side is-style-lpu-split-frame-ecru","layout":{"type":"constrained"},"metadata":{"name":"Gauche — contenu éditorial"}} -->
			<div class="wp-block-group lpu-split-v1__side is-style-lpu-split-frame-ecru">
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
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"width":"50%","className":"lpu-split-v1__column"} -->
		<div class="wp-block-column lpu-split-v1__column" style="flex-basis:50%">
			<!-- wp:group {"className":"lpu-split-v1__side is-style-lpu-split-frame-none","layout":{"type":"constrained"},"metadata":{"name":"Droite — image pleine zone"}} -->
			<div class="wp-block-group lpu-split-v1__side is-style-lpu-split-frame-none">
				<!-- wp:image {"url":"/wp-content/themes/lepaysanurbain/assets/images/pattern-placeholder.svg","alt":"","className":"lpu-media-placeholder","linkDestination":"none"} -->
				<figure class="wp-block-image lpu-media-placeholder"><img src="/wp-content/themes/lepaysanurbain/assets/images/pattern-placeholder.svg" alt="" /></figure>
				<!-- /wp:image -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</div>
<!-- /wp:group -->
