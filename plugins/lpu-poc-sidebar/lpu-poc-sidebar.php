<?php
/**
 * Plugin Name: LPU PoC Sidebar
 * Description: Preuve de concept jetable : PluginSidebar dans l'éditeur de site. À supprimer après validation.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'enqueue_block_editor_assets',
	function () {
		wp_enqueue_script(
			'lpu-poc-sidebar',
			plugins_url( 'sidebar.js', __FILE__ ),
			array( 'wp-plugins', 'wp-element', 'wp-components', 'wp-data', 'wp-editor' ),
			'1.0',
			true
		);
	}
);
