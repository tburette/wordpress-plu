<?php
/**
 * Title: Hero — Cultiver le vivant
 * Slug: lepaysanurbain/hero
 * Categories: lpu-sections
 * Description: Hero pleine largeur avec image à choisir, titre, texte et appel à l’action.
 * Keywords: hero, accueil, image
 * Post Types: page
 */
?>
<!-- wp:cover {"url":"/wp-content/themes/lepaysanurbain/assets/images/pattern-placeholder.svg","dimRatio":30,"isDark":true,"align":"full","backgroundColor":"vert-fonce","className":"lpu-band lpu-hero","layout":{"type":"constrained"}} -->
<div class="wp-block-cover alignfull lpu-band lpu-hero has-vert-fonce-background-color has-background">
	<img class="wp-block-cover__image-background" alt="" src="/wp-content/themes/lepaysanurbain/assets/images/pattern-placeholder.svg" data-object-fit="cover" />
	<span aria-hidden="true" class="wp-block-cover__background has-background-dim-30 has-background-dim"></span>
	<div class="wp-block-cover__inner-container">
		<!-- wp:group {"align":"wide","className":"lpu-hero__content","layout":{"type":"constrained"}} -->
		<div class="wp-block-group alignwide lpu-hero__content">
			<!-- wp:heading {"textColor":"ecru","fontSize":"display","level":1} -->
			<h1 class="wp-block-heading has-ecru-color has-text-color has-display-font-size">Cultiver le vivant en ville.</h1>
			<!-- /wp:heading -->

			<!-- wp:paragraph {"textColor":"ecru","fontSize":"text"} -->
			<p class="has-ecru-color has-text-color has-text-font-size">Présentez ici la promesse de cette page et le rôle du Paysan Urbain dans la ville.</p>
			<!-- /wp:paragraph -->

			<!-- wp:buttons {"className":"lpu-hero__actions"} -->
			<div class="wp-block-buttons lpu-hero__actions">
				<!-- wp:button {"className":"lpu-button-on-color"} -->
				<div class="wp-block-button lpu-button-on-color"><a class="wp-block-button__link wp-element-button">En savoir plus</a></div>
				<!-- /wp:button -->
			</div>
			<!-- /wp:buttons -->
		</div>
		<!-- /wp:group -->
	</div>
</div>
<!-- /wp:cover -->
