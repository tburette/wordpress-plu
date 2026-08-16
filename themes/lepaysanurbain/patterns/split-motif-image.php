<?php
/**
 * Title: Côte à côte — motif et image (Core)
 * Slug: lepaysanurbain/split-motif-image
 * Categories: lpu-sections
 * Description: Deux moitiés indépendantes : un cadre motif carré avec panneau intérieur à gauche et une image pleine zone à droite.
 * Keywords: côte à côte, motif, cadre, image, Core
 * Post Types: page
 */
?>
<!-- wp:group {"align":"full","className":"lpu-split-v1 lpu-split-v1--motif-image","layout":{"type":"default"}} -->
<div class="wp-block-group alignfull lpu-split-v1 lpu-split-v1--motif-image">
	<!-- wp:columns {"align":"full","className":"lpu-split-v1__columns"} -->
	<div class="wp-block-columns alignfull lpu-split-v1__columns">
		<!-- wp:column {"width":"50%","className":"lpu-split-v1__column"} -->
		<div class="wp-block-column lpu-split-v1__column" style="flex-basis:50%">
			<!-- wp:group {"className":"lpu-split-v1__side is-style-lpu-split-frame-motif-7","layout":{"type":"constrained"},"metadata":{"name":"Gauche — cadre motif et contenu"}} -->
			<div class="wp-block-group lpu-split-v1__side is-style-lpu-split-frame-motif-7">
				<!-- wp:group {"backgroundColor":"ecru","className":"lpu-split-v1__inset","layout":{"type":"constrained"}} -->
				<div class="wp-block-group lpu-split-v1__inset has-ecru-background-color has-background">
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
