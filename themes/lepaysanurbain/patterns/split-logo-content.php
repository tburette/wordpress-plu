<?php
/**
 * Title: Côte à côte — logo et titre-texte (Core)
 * Slug: lepaysanurbain/split-logo-content
 * Categories: lpu-sections
 * Description: Deux moitiés indépendantes : identité visuelle à gauche et contenu éditorial à droite.
 * Keywords: côte à côte, logo, titre, texte, Core
 * Post Types: page
 */
?>
<!-- wp:group {"align":"full","className":"lpu-split-v1 lpu-split-v1--logo-content","layout":{"type":"default"}} -->
<div class="wp-block-group alignfull lpu-split-v1 lpu-split-v1--logo-content">
	<!-- wp:columns {"align":"full","className":"lpu-split-v1__columns"} -->
	<div class="wp-block-columns alignfull lpu-split-v1__columns">
		<!-- wp:column {"width":"50%","className":"lpu-split-v1__column"} -->
		<div class="wp-block-column lpu-split-v1__column" style="flex-basis:50%">
			<!-- wp:group {"className":"lpu-split-v1__side lpu-split-v1__side--logo is-style-lpu-split-frame-green","layout":{"type":"constrained"},"metadata":{"name":"Gauche — identité visuelle"}} -->
			<div class="wp-block-group lpu-split-v1__side lpu-split-v1__side--logo is-style-lpu-split-frame-green">
				<!-- wp:image {"url":"/wp-content/themes/lepaysanurbain/assets/images/logos/network-horizontal-ecru-baseline.svg","alt":"Le Paysan Urbain","linkDestination":"none"} -->
				<figure class="wp-block-image"><img src="/wp-content/themes/lepaysanurbain/assets/images/logos/network-horizontal-ecru-baseline.svg" alt="Le Paysan Urbain" /></figure>
				<!-- /wp:image -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"width":"50%","className":"lpu-split-v1__column"} -->
		<div class="wp-block-column lpu-split-v1__column" style="flex-basis:50%">
			<!-- wp:group {"className":"lpu-split-v1__side is-style-lpu-split-frame-ecru","layout":{"type":"constrained"},"metadata":{"name":"Droite — titre et texte"}} -->
			<div class="wp-block-group lpu-split-v1__side is-style-lpu-split-frame-ecru">
				<!-- wp:paragraph {"fontFamily":"oswald","fontSize":"text","className":"lpu-eyebrow"} -->
				<p class="lpu-eyebrow has-oswald-font-family has-text-font-size">Qui sommes-nous&nbsp;?</p>
				<!-- /wp:paragraph -->

				<!-- wp:heading {"level":2,"fontSize":"subtitle"} -->
				<h2 class="wp-block-heading has-subtitle-font-size">Un titre et un texte dans l’autre moitié</h2>
				<!-- /wp:heading -->

				<!-- wp:paragraph -->
				<p>Cette zone reste indépendante : ajoutez, retirez ou réorganisez ses blocs sans toucher à la moitié gauche.</p>
				<!-- /wp:paragraph -->

				<!-- wp:buttons -->
				<div class="wp-block-buttons">
					<!-- wp:button -->
					<div class="wp-block-button"><a class="wp-block-button__link wp-element-button">Découvrir</a></div>
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
