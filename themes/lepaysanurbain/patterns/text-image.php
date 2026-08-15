<?php
/**
 * Title: Texte et image
 * Slug: lepaysanurbain/text-image
 * Categories: lpu-sections
 * Description: Section en deux colonnes avec titre, texte, bouton et image à choisir.
 * Keywords: texte, image, colonnes, présentation
 * Post Types: page
 */
?>
<!-- wp:group {"align":"full","backgroundColor":"ecru","className":"lpu-band lpu-text-image","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull lpu-band lpu-text-image has-ecru-background-color has-background">
	<!-- wp:columns {"align":"wide","verticalAlignment":"center","className":"lpu-text-image__columns"} -->
	<div class="wp-block-columns alignwide are-vertically-aligned-center lpu-text-image__columns">
		<!-- wp:column {"verticalAlignment":"center","width":"50%","className":"lpu-text-image__content"} -->
		<div class="wp-block-column is-vertically-aligned-center lpu-text-image__content" style="flex-basis:50%">
			<!-- wp:paragraph {"fontFamily":"oswald","fontSize":"text","className":"lpu-eyebrow"} -->
			<p class="lpu-eyebrow has-oswald-font-family has-text-font-size">Sur-titre</p>
			<!-- /wp:paragraph -->

			<!-- wp:heading {"level":2,"fontSize":"subtitle"} -->
			<h2 class="wp-block-heading has-subtitle-font-size">Titre de la section</h2>
			<!-- /wp:heading -->

			<!-- wp:paragraph -->
			<p>Présentez ici le contenu principal de cette section.</p>
			<!-- /wp:paragraph -->

			<!-- wp:buttons -->
			<div class="wp-block-buttons">
				<!-- wp:button -->
				<div class="wp-block-button"><a class="wp-block-button__link wp-element-button">En savoir plus</a></div>
				<!-- /wp:button -->
			</div>
			<!-- /wp:buttons -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"verticalAlignment":"center","width":"50%","className":"lpu-text-image__media-column"} -->
		<div class="wp-block-column is-vertically-aligned-center lpu-text-image__media-column" style="flex-basis:50%">
			<!-- wp:image {"url":"/wp-content/themes/lepaysanurbain/assets/images/pattern-placeholder.svg","alt":"","className":"lpu-media-placeholder","linkDestination":"none"} -->
			<figure class="wp-block-image lpu-media-placeholder"><img src="/wp-content/themes/lepaysanurbain/assets/images/pattern-placeholder.svg" alt="" /></figure>
			<!-- /wp:image -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</div>
<!-- /wp:group -->
