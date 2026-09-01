<?php

/**
 * Plugin Name: Nav Group (TEST)
 * Description: Throwaway spike for grouping navigation items inside core/navigation.
 * Version: 0.1.0
 * Text Domain: nav-group-spike
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
 * 'block_type_metadata' allows changing the metadata (from the block.json) of 
 * a block before it is registered
 */
add_filter(
	'block_type_metadata',
	function ($metadata) {
		if (in_array($metadata['name'] ?? '', array('core/navigation-link', 'core/navigation-submenu'), true)) {
			// make them accept our custom block as their parent
			$metadata['parent'] = array_merge(
				$metadata['parent'] ?? array(),
				array('lpu/nav-group')
			);
		}

		return $metadata;
	}
);
