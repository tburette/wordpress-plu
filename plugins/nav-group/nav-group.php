<?php

/**
 * Plugin Name: Nav Group
 * Description: Structural grouping block for Core Navigation.
 * Version: 1.0.0
 * Text Domain: nav-group
 */

if (! defined('ABSPATH')) {
	exit;
}

add_action(
	'init',
	function () {
		register_block_type(__DIR__ . '/build');
	}
);

/**
 * Core's Navigation block only allows its registered child types as parents.
 * Extend the two block types that may live inside Nav Group so Gutenberg keeps
 * treating them as normal Navigation content while the group supplies the
 * structural wrapper at render time.
 */
add_filter(
	'block_type_metadata',
	function ($metadata) {
		if (in_array($metadata['name'] ?? '', array('core/navigation-link', 'core/navigation-submenu'), true)) {
			// Allow these blocks to be children of Nav Group.
			$metadata['parent'] = array_merge(
				$metadata['parent'] ?? array(),
				array('lpu/nav-group')
			);
		}

		return $metadata;
	}
);
