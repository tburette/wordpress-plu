<?php
/**
 * LPU Provisioning network-admin trigger (the no-SSH/OVH path).
 *
 * @package Lpu_Provisioning
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Network-admin screen + admin-post handler that run the same provisioner
 * used by the WP-CLI command. Intended for the OVH target which has no SSH
 * or WP-CLI: upload the plugin, open this page, click "Provisionner".
 */
class Lpu_Provision_Admin {

	const ACTION = 'lpu_provision_run';
	const NONCE  = 'lpu_provision_run';
	const OPTION = 'lpu_provision_result';

	/**
	 * Register the network-admin menu page.
	 *
	 * @return void
	 */
	public function register_menu() {
		add_submenu_page(
			'settings.php',
			'Provisionnement Le Paysan Urbain',
			'Provisionnement LPU',
			'manage_network',
			'lpu-provisioning',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Register the admin-post handler.
	 *
	 * @return void
	 */
	public function register_handler() {
		add_action( 'admin_post_' . self::ACTION, array( $this, 'handle_run' ) );
	}

	/**
	 * Handle the provisioning POST: verify capability + nonce, run the
	 * provisioner, store the result, redirect back to the page.
	 *
	 * @return void
	 */
	public function handle_run() {
		if ( ! current_user_can( 'manage_network' ) ) {
			wp_die( esc_html__( 'Permission refusée.', 'lpu-provisioning' ) );
		}
		check_admin_referer( self::NONCE );

		// Long synchronous run on hosts without CLI: lift PHP limits.
		set_time_limit( 0 );
		wp_raise_memory_limit( 'admin' );

		// Now that the run has started, surface a clear error if the PHP
		// process dies before the result is stored (hard timeout, memory
		// exhaustion, or a server-level interruption) — those bypass
		// catch (Throwable). The normal path flips $reached_store before exit.
		$reached_store = false;
		register_shutdown_function(
			function () use ( &$reached_store ) {
				if ( $reached_store ) {
					return;
				}
				$result                = get_site_option( self::OPTION, array() );
				$result['time']        = current_time( 'mysql' );
				$result['error']       = 'Exécution interrompue avant son terme (timeout, épuisement mémoire ou interruption du serveur). Résultat partiel — relancez le bouton.';
				$result['interrupted'] = true;
				update_site_option( self::OPTION, $result );
			}
		);

		$force = ! empty( $_POST['force'] );
		$log   = array( 'time' => current_time( 'mysql' ), 'lines' => array() );
		$error = '';

		try {
			$provisioner = new Lpu_Provisioner();
			$provisioner->provision( $force );
		} catch ( Throwable $e ) {
			$error = $e->getMessage();
		}

		$log['lines'] = Lpu_Provisioner::get_log();
		$log['error'] = $error;
		$reached_store = true;
		update_site_option( self::OPTION, $log );

		wp_safe_redirect(
			network_admin_url( 'settings.php?page=lpu-provisioning' )
		);
		exit;
	}

	/**
	 * Render the network-admin page.
	 *
	 * @return void
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_network' ) ) {
			return;
		}

		$provisioner = new Lpu_Provisioner();
		$steps       = $provisioner->steps();
		$result      = get_site_option( self::OPTION, array() );
		?>
		<div class="wrap">
			<h1>Provisionnement Le Paysan Urbain</h1>
			<p>Applique le contenu de développement et la configuration sur ce multisite. La plupart des étapes sont idempotentes et peuvent être relancées sans risque. La page d’accueil réseau est protégée : si elle contient déjà du contenu édité, cochez la case « forcée » pour la reconstruire.</p>

			<?php if ( ! empty( $result ) ) : ?>
				<h2>Dernière exécution (<?php echo esc_html( $result['time'] ); ?>)</h2>
				<?php if ( ! empty( $result['error'] ) ) : ?>
					<div class="notice notice-error"><p><?php echo esc_html( $result['error'] ); ?></p></div>
				<?php else : ?>
					<div class="notice notice-success"><p>Provisionnement terminé sans erreur.</p></div>
				<?php endif; ?>
				<pre style="max-height:400px;overflow:auto;background:#fff;border:1px solid #ccd0d4;padding:12px;"><?php echo esc_html( implode( "\n", $result['lines'] ) ); ?></pre>
				<?php
			endif;
			?>

			<h2>Étapes</h2>
			<ol>
				<?php foreach ( $steps as $step ) : ?>
					<li><?php echo esc_html( $step['label'] ); ?></li>
				<?php endforeach; ?>
			</ol>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( self::NONCE ); ?>
				<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION ); ?>" />
				<p>
					<label>
						<input type="checkbox" name="force" value="1" />
						Remplacer une page d’accueil déjà assemblée (équivaut à <code>--force</code>)
					</label>
				</p>
				<?php submit_button( 'Provisionner le site', 'primary', 'submit' ); ?>
			</form>
		</div>
		<?php
	}
}
