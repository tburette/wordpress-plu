<?php
/**
 * Title: Grille de cartes
 * Slug: lepaysanurbain/cards
 * Categories: lpu-sections
 * Description: Grille de cartes avec image, titre, description et appel à l’action facultatifs.
 * Keywords: cartes, grille, publics, actions
 * Post Types: page
 */
?>
<!-- wp:group {"align":"full","backgroundColor":"ecru","className":"lpu-card-grid lpu-motif lpu-motif-1-bandeau","layout":{"type":"constrained"},"style":{"spacing":{"padding":{"top":"var:preset|spacing|xl","right":"var:preset|spacing|lg","bottom":"var:preset|spacing|xl","left":"var:preset|spacing|lg"}},"@tablet":{"spacing":{"padding":{"right":"var:preset|spacing|md","left":"var:preset|spacing|md"}}},"@mobile":{"spacing":{"padding":{"right":"var:preset|spacing|md","left":"var:preset|spacing|md"}}}}} -->
<div class="wp-block-group alignfull lpu-card-grid lpu-motif lpu-motif-1-bandeau has-ecru-background-color has-background" style="padding-top:var(--wp--preset--spacing--xl);padding-right:var(--wp--preset--spacing--lg);padding-bottom:var(--wp--preset--spacing--xl);padding-left:var(--wp--preset--spacing--lg)">
	<!-- wp:heading {"align":"wide","level":2,"fontSize":"subtitle","className":"lpu-card-grid__title"} -->
	<h2 class="wp-block-heading alignwide lpu-card-grid__title has-subtitle-font-size">Titre de la grille</h2>
	<!-- /wp:heading -->

	<!-- wp:columns {"align":"wide","className":"lpu-card-grid__columns"} -->
	<div class="wp-block-columns alignwide lpu-card-grid__columns">
		<!-- wp:column {"className":"lpu-card"} -->
		<div class="wp-block-column lpu-card">
			<!-- wp:image {"url":"/wp-content/themes/lepaysanurbain/assets/images/pattern-placeholder.svg","alt":"","className":"lpu-media-placeholder","linkDestination":"none"} -->
			<figure class="wp-block-image lpu-media-placeholder"><img src="/wp-content/themes/lepaysanurbain/assets/images/pattern-placeholder.svg" alt="" /></figure>
			<!-- /wp:image -->

			<!-- wp:group {"className":"lpu-card__body","layout":{"type":"flex","orientation":"vertical"},"style":{"spacing":{"padding":{"top":"var:preset|spacing|sm","bottom":"0"}}}} -->
			<div class="wp-block-group lpu-card__body is-layout-flex is-vertical" style="padding-top:var(--wp--preset--spacing--sm);padding-bottom:0">
				<!-- wp:heading {"level":3,"fontFamily":"oswald","fontSize":"text"} -->
				<h3 class="wp-block-heading has-oswald-font-family has-text-font-size">Titre de carte 1</h3>
				<!-- /wp:heading -->

				<!-- wp:paragraph {"fontSize":"text"} -->
				<p class="has-text-font-size">Décrivez brièvement le contenu de cette carte et son intérêt pour vos visiteurs.</p>
				<!-- /wp:paragraph -->

				<!-- wp:buttons {"style":{"spacing":{"padding":{"top":"var:preset|spacing|sm"}}}} -->
				<div class="wp-block-buttons" style="padding-top:var(--wp--preset--spacing--sm)">
					<!-- wp:button {"style":{"spacing":{"padding":{"top":"0.4rem","right":"0.85rem","bottom":"0.4rem","left":"0.85rem"}}}} -->
					<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" style="padding-top:0.4rem;padding-right:0.85rem;padding-bottom:0.4rem;padding-left:0.85rem">En savoir plus</a></div>
					<!-- /wp:button -->
				</div>
				<!-- /wp:buttons -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"className":"lpu-card"} -->
		<div class="wp-block-column lpu-card">
			<!-- wp:image {"url":"/wp-content/themes/lepaysanurbain/assets/images/pattern-placeholder.svg","alt":"","className":"lpu-media-placeholder","linkDestination":"none"} -->
			<figure class="wp-block-image lpu-media-placeholder"><img src="/wp-content/themes/lepaysanurbain/assets/images/pattern-placeholder.svg" alt="" /></figure>
			<!-- /wp:image -->

			<!-- wp:group {"className":"lpu-card__body","layout":{"type":"flex","orientation":"vertical"},"style":{"spacing":{"padding":{"top":"var:preset|spacing|sm","bottom":"0"}}}} -->
			<div class="wp-block-group lpu-card__body is-layout-flex is-vertical" style="padding-top:var(--wp--preset--spacing--sm);padding-bottom:0">
				<!-- wp:heading {"level":3,"fontFamily":"oswald","fontSize":"text"} -->
				<h3 class="wp-block-heading has-oswald-font-family has-text-font-size">Titre de carte 2</h3>
				<!-- /wp:heading -->

				<!-- wp:paragraph {"fontSize":"text"} -->
				<p class="has-text-font-size">Ajoutez une information courte sur cette proposition.</p>
				<!-- /wp:paragraph -->

				<!-- wp:buttons {"style":{"spacing":{"padding":{"top":"var:preset|spacing|sm"}}}} -->
				<div class="wp-block-buttons" style="padding-top:var(--wp--preset--spacing--sm)">
					<!-- wp:button {"style":{"spacing":{"padding":{"top":"0.4rem","right":"0.85rem","bottom":"0.4rem","left":"0.85rem"}}}} -->
					<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" style="padding-top:0.4rem;padding-right:0.85rem;padding-bottom:0.4rem;padding-left:0.85rem">En savoir plus</a></div>
					<!-- /wp:button -->
				</div>
				<!-- /wp:buttons -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"className":"lpu-card"} -->
		<div class="wp-block-column lpu-card">
			<!-- wp:image {"url":"/wp-content/themes/lepaysanurbain/assets/images/pattern-placeholder.svg","alt":"","className":"lpu-media-placeholder","linkDestination":"none"} -->
			<figure class="wp-block-image lpu-media-placeholder"><img src="/wp-content/themes/lepaysanurbain/assets/images/pattern-placeholder.svg" alt="" /></figure>
			<!-- /wp:image -->

			<!-- wp:group {"className":"lpu-card__body","layout":{"type":"flex","orientation":"vertical"},"style":{"spacing":{"padding":{"top":"var:preset|spacing|sm","bottom":"0"}}}} -->
			<div class="wp-block-group lpu-card__body is-layout-flex is-vertical" style="padding-top:var(--wp--preset--spacing--sm);padding-bottom:0">
				<!-- wp:heading {"level":3,"fontFamily":"oswald","fontSize":"text"} -->
				<h3 class="wp-block-heading has-oswald-font-family has-text-font-size">Titre de carte 3</h3>
				<!-- /wp:heading -->

				<!-- wp:paragraph {"fontSize":"text"} -->
				<p class="has-text-font-size">Présentez un troisième contenu ou une action à découvrir.</p>
				<!-- /wp:paragraph -->

				<!-- wp:buttons {"style":{"spacing":{"padding":{"top":"var:preset|spacing|sm"}}}} -->
				<div class="wp-block-buttons" style="padding-top:var(--wp--preset--spacing--sm)">
					<!-- wp:button {"style":{"spacing":{"padding":{"top":"0.4rem","right":"0.85rem","bottom":"0.4rem","left":"0.85rem"}}}} -->
					<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" style="padding-top:0.4rem;padding-right:0.85rem;padding-bottom:0.4rem;padding-left:0.85rem">En savoir plus</a></div>
					<!-- /wp:button -->
				</div>
				<!-- /wp:buttons -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</div>
<!-- /wp:group -->
