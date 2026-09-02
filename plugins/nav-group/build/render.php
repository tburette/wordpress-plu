<?php

/**
 * Server-side render for lpu/nav-group.
 *
 * @var array    $attributes
 * @var string   $content
 * @var WP_Block $block
 */

$inner_html = '';

foreach ($block->inner_blocks as $inner_block) {
	$inner_html .= $inner_block->render();
}

// If the group is empty (no links added yet), render nothing rather than an
// empty <li><ul></ul></li> — matches how other empty nav items behave.
if ('' === trim($inner_html)) {
	return;
}

printf(
	'<li class="wp-block-navigation-item lpu-nav-group"><ul class="lpu-nav-group__list">%s</ul></li>',
	$inner_html
);
