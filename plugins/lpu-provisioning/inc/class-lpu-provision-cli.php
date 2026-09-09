<?php
/**
 * LPU Provisioning WP-CLI command.
 *
 * @package Lpu_Provisioning
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * `wp lpu provision` — runs the full provisioning in one WP-CLI invocation.
 */
class Lpu_Provision_CLI {

	/**
	 * Run the full provisioning.
	 *
	 * ## EXAMPLES
	 *
	 *     wp lpu provision
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 * @return void
	 */
	public function __invoke( $args, $assoc_args ) {
		$provisioner = new Lpu_Provisioner();
		$provisioner->provision();
	}
}
