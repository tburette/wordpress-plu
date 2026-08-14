<?php
/**
 * Title: Image et texte avec motif — mise en avant
 * Slug: lepaysanurbain/text-image-motif
 * Categories: lpu-sections
 * Description: Variante de mise en avant avec image entourée d’un motif à gauche et contenu à droite.
 * Keywords: image, texte, motif, mise en avant
 * Post Types: page
 */
?>
<!-- wp:group {"align":"full","backgroundColor":"ecru","className":"lpu-band lpu-text-image-motif","layout":{"type":"default"}} -->
<div class="wp-block-group alignfull lpu-band lpu-text-image-motif has-ecru-background-color has-background">
	<!-- wp:columns {"align":"full","className":"lpu-text-image-motif__columns"} -->
	<div class="wp-block-columns alignfull lpu-text-image-motif__columns">
		<!-- wp:column {"width":"50%","className":"lpu-text-image-motif__media-column"} -->
		<div class="wp-block-column lpu-text-image-motif__media-column" style="flex-basis:50%">
			<!-- wp:group {"className":"lpu-text-image-motif__media lpu-motif lpu-motif-4-bandeau","layout":{"type":"constrained"}} -->
			<div class="wp-block-group lpu-text-image-motif__media lpu-motif lpu-motif-4-bandeau">
				<!-- wp:image {"url":"/wp-content/themes/lepaysanurbain/assets/images/pattern-placeholder.svg","alt":"","className":"lpu-media-placeholder","linkDestination":"none"} -->
				<figure class="wp-block-image lpu-media-placeholder"><img src="/wp-content/themes/lepaysanurbain/assets/images/pattern-placeholder.svg" alt="" /></figure>
				<!-- /wp:image -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"width":"50%","className":"lpu-text-image-motif__content"} -->
		<div class="wp-block-column lpu-text-image-motif__content" style="flex-basis:50%">
			<!-- wp:paragraph {"fontFamily":"oswald","fontSize":"text","className":"lpu-eyebrow"} -->
			<p class="lpu-eyebrow has-oswald-font-family has-text-font-size">Une ferme, des savoir-faire</p>
			<!-- /wp:paragraph -->

			<!-- wp:heading {"level":2,"fontSize":"subtitle"} -->
			<h2 class="wp-block-heading has-subtitle-font-size">Cultiver et transmettre au quotidien</h2>
			<!-- /wp:heading -->

			<!-- wp:paragraph -->
			<p>Décrivez ici l’action mise en avant, les personnes concernées et la manière dont cette initiative fait grandir le vivant en ville.</p>
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
	</div>
	<!-- /wp:columns -->
</div>
<!-- /wp:group -->
