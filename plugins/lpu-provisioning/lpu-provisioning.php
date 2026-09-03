<?php
/**
 * Plugin Name: LPU — Provisioning
 * Description: Provisionne la configuration et le contenu du site Le Paysan Urbain, en local (WP-CLI) et sur un hébergement sans SSH/WP-CLI (écran d’administration).
 * Version: 0.1.0
 * Requires at least: 6.4
 * Requires PHP: 7.4
 * Network: true
 * Text Domain: lpu-provisioning
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/inc/class-lpu-provisioner.php';
require_once __DIR__ . '/inc/class-lpu-provision-cli.php';

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command( 'lpu provision', 'Lpu_Provision_CLI' );
}

if ( is_admin() ) {
	require_once __DIR__ . '/inc/class-lpu-provision-admin.php';
}

/**
 * Register the network-admin page.
 *
 * @return void
 */
function lpu_provisioning_admin_menu() {
	if ( ! is_multisite() ) {
		return;
	}
	$admin = new Lpu_Provision_Admin();
	$admin->register_menu();
}
add_action( 'network_admin_menu', 'lpu_provisioning_admin_menu' );

/**
 * Register the admin-post handler.
 *
 * Registered on admin_init (not network_admin_menu) because an admin-post
 * round trip loads admin-post.php without firing network_admin_menu; this
 * must run on any admin request regardless of which admin page is loaded.
 *
 * @return void
 */
function lpu_provisioning_admin_handler() {
	if ( ! is_multisite() ) {
		return;
	}
	$admin = new Lpu_Provision_Admin();
	$admin->register_handler();
}
add_action( 'admin_init', 'lpu_provisioning_admin_handler' );
