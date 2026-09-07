<?php
/**
 * Title: Bandeau graphique
 * Slug: lepaysanurbain/graphic-band
 * Categories: lpu-sections
 * Description: Bandeau final coloré avec contenu centré et bouton.
 * Keywords: bandeau, couleur, appel à l’action, conclusion
 * Post Types: page
 */
?>
<!-- wp:group {"align":"full","backgroundColor":"jaune","className":"lpu-band lpu-graphic-band","layout":{"type":"constrained"},"style":{"spacing":{"padding":{"top":"var:preset|spacing|section","right":"var:preset|spacing|lg","bottom":"var:preset|spacing|section","left":"var:preset|spacing|lg"}},"@tablet":{"spacing":{"padding":{"right":"var:preset|spacing|md","left":"var:preset|spacing|md"}}},"@mobile":{"spacing":{"padding":{"right":"var:preset|spacing|md","left":"var:preset|spacing|md"}}}}} -->
<div class="wp-block-group alignfull lpu-band lpu-graphic-band has-jaune-background-color has-background" style="padding-top:var(--wp--preset--spacing--section);padding-right:var(--wp--preset--spacing--lg);padding-bottom:var(--wp--preset--spacing--section);padding-left:var(--wp--preset--spacing--lg)">
	<!-- wp:heading {"textAlign":"center","level":2,"fontSize":"subtitle"} -->
	<h2 class="wp-block-heading has-text-align-center has-subtitle-font-size">Titre de l’appel à l’action</h2>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"align":"center"} -->
	<p class="has-text-align-center">Ajoutez ici une phrase courte pour guider vos visiteurs.</p>
	<!-- /wp:paragraph -->

	<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
	<div class="wp-block-buttons">
		<!-- wp:button -->
		<div class="wp-block-button"><a class="wp-block-button__link wp-element-button">En savoir plus</a></div>
		<!-- /wp:button -->
	</div>
	<!-- /wp:buttons -->
</div>
<!-- /wp:group -->
