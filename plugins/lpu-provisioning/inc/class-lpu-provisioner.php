<?php
/**
 * LPU Provisioner.
 *
 * One implementation of the Le Paysan Urbain provisioning, runnable both from
 * WP-CLI (`wp lpu provision`) and from a network-admin page (for the shared
 * OVH hosting that has no SSH or WP-CLI). Every method is idempotent:
 * find-or-create, never silently overwrite editorial content.
 *
 * This class only declares the provisioning *steps*. The plumbing they rely on
 * (reading content files, switching blog context, importing media, assembling
 * pages, logging) lives in the Lpu_Util trait (inc/class-lpu-util.php) so the
 * steps stay readable top-to-bottom, like the original shell scripts.
 *
 * @package Lpu_Provisioning
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/class-lpu-util.php';

/**
 * Core provisioning logic.
 */
class Lpu_Provisioner {

	use Lpu_Util;

	/**
	 * Run every provisioning step in dependency order.
	 *
	 * @param bool $force Allow replacing an already assembled network Home.
	 * @return void
	 */
	public function provision( $force = false ) {
		$this->force = (bool) $force;

		$this->check_dependencies();
		$this->register_theme_patterns();
		$this->log( '==> Provisioning Le Paysan Urbain' );
		$this->provision_network_sites();
		$this->provision_theme();
		$this->provision_language();
		$this->provision_logos();
		$this->provision_front_pages();
		$this->provision_navigations();
		$this->provision_footers();
		$this->provision_test_page();
		$this->provision_patterns_test_page();
		$this->provision_home_network();
		$this->log( 'Provisioning complete.' );
	}

	/**
	 * Return the set of provisioning steps (id, label) for the admin screen.
	 *
	 * @return array<int, array<string, string>>
	 */
	public function steps() {
		return array(
			array( 'id' => 'network', 'label' => 'Multisite network and sub-sites' ),
			array( 'id' => 'theme', 'label' => 'Theme enable and activation' ),
			array( 'id' => 'language', 'label' => 'French locale' ),
			array( 'id' => 'logos', 'label' => 'Site logos' ),
			array( 'id' => 'pages', 'label' => 'Front pages' ),
			array( 'id' => 'navigations', 'label' => 'Header navigations and template parts' ),
			array( 'id' => 'footers', 'label' => 'Footer navigations and template parts' ),
			array( 'id' => 'test-page', 'label' => 'Typography test page' ),
			array( 'id' => 'patterns-test-page', 'label' => 'Patterns test page' ),
			array( 'id' => 'home', 'label' => 'Network Home' ),
		);
	}

	/**
	 * Step: multisite network and sub-sites exist.
	 *
	 * @return void
	 */
	public function provision_network_sites() {
		$this->ensure_blogs();
	}

	/**
	 * Step: enable and activate the theme on every site.
	 *
	 * @return void
	 */
	public function provision_theme() {
		$theme = wp_get_theme( self::THEME_SLUG );
		if ( ! $theme->exists() ) {
			$this->fail( 'Theme not found: ' . self::THEME_SLUG );
		}

		// Network-enable the theme (like `wp theme enable --network`).
		$allowed = (array) get_site_option( 'allowedthemes', array() );
		if ( empty( $allowed[ self::THEME_SLUG ] ) ) {
			$allowed[ self::THEME_SLUG ] = true;
			update_site_option( 'allowedthemes', $allowed );
			$this->log( 'Network-enabled theme: ' . self::THEME_SLUG );
		}
		wp_clean_themes_cache();

		$this->ensure_blogs();
		foreach ( $this->blogs as $role => $blog_id ) {
			$this->with_blog(
				$blog_id,
				function () use ( $role ) {
					if ( self::THEME_SLUG !== get_option( 'template' ) || self::THEME_SLUG !== get_option( 'stylesheet' ) ) {
						switch_theme( self::THEME_SLUG );
						wp_clean_themes_cache();
						$this->log( $role . ': theme ' . self::THEME_SLUG . ' active' );
					}
				}
			);
		}
	}

	/**
	 * Step: ensure the French language pack and locale are active everywhere.
	 *
	 * The fr_FR core language pack is installed once (shared across the
	 * network) via install_language_pack() — the PHP equivalent of the shell's
	 * `wp language core install fr_FR`. Then the French locale (WPLANG) is
	 * recorded on every site, and the network admin's profile language is set
	 * to French so wp-admin is French even when that user has an explicit
	 * locale preference.
	 *
	 * @return void
	 */
	public function provision_language() {
		$locale = 'fr_FR';
		$this->ensure_blogs();

		$this->install_language_pack( $locale );

		foreach ( $this->blogs as $role => $blog_id ) {
			$this->with_blog(
				$blog_id,
				function () use ( $role, $locale ) {
					if ( $locale !== (string) get_option( 'WPLANG' ) ) {
						update_option( 'WPLANG', $locale );
						$this->log( $role . ': language ' . $locale . ' active' );
					}
				}
			);
		}

		// Force French on the network admin's profile so wp-admin is French
		// even when that user has an explicit locale preference. Target the
		// first network super admin (not a hardcoded "admin" login, which may
		// not exist on a fresh/OVH network) and fall back to the current user.
		$super_admin = null;
		foreach ( get_super_admins() as $login ) {
			$user = get_user_by( 'login', $login );
			if ( $user ) {
				$super_admin = $user;
				break;
			}
		}
		if ( ! $super_admin ) {
			$current = wp_get_current_user();
			if ( $current instanceof WP_User && $current->ID ) {
				$super_admin = $current;
			}
		}

		if ( $super_admin ) {
			// Network-wide so the admin UI is French on every site.
			update_user_option( $super_admin->ID, 'locale', $locale, true );
			$this->log( $super_admin->user_login . ': user locale ' . $locale );
		}
	}

	/**
	 * Step: import and select the per-site logos.
	 *
	 * @return void
	 */
	public function provision_logos() {
		$logos_dir = get_theme_root() . '/' . self::THEME_SLUG . '/assets/images/logos';

		$this->each_site_row(
			'logos-sites.tsv',
			5,
			'logo',
			function ( $row ) use ( $logos_dir ) {
				$role                   = $row[0];
				$logo_title             = $row[1];
				$logo_file              = $row[2];
				$transparent_logo_title = $row[3];
				$transparent_logo_file  = $row[4];

				$logo_id = $this->import_attachment( $logos_dir . '/' . $logo_file, $logo_title );
				set_theme_mod( 'custom_logo', $logo_id );

				$transparent_logo_id = $this->import_attachment( $logos_dir . '/' . $transparent_logo_file, $transparent_logo_title );
				set_theme_mod( 'lpu_transparent_logo', $transparent_logo_id );

				$this->log( $role . ': custom_logo ' . $logo_id . ' (' . $logo_file . '), transparent ' . $transparent_logo_id );
			}
		);
	}

	/**
	 * Step: create or select the static front page on every site.
	 *
	 * @return void
	 */
	public function provision_front_pages() {
		$page_content = $this->read_content( 'accueil.html' );

		$this->each_site_row(
			'sites.tsv',
			3,
			'front-page',
			function ( $row ) use ( $page_content ) {
				$role       = $row[0];
				$page_title = $row[1];
				$page_slug  = $row[2];

				$page_id = $this->find_post_by_name( 'page', $page_slug );
				if ( ! $page_id ) {
					$page_id = wp_insert_post(
						array(
							'post_type'    => 'page',
							'post_status'  => 'publish',
							'post_title'   => $page_title,
							'post_name'    => $page_slug,
							'post_content' => $page_content,
						),
						true
					);
					if ( is_wp_error( $page_id ) ) {
						$this->fail( $page_id->get_error_message() );
					}
					$this->log( $role . ': created front page ' . $page_id );
				}

				update_option( 'show_on_front', 'page' );
				update_option( 'page_on_front', (int) $page_id );
			}
		);
	}

	/**
	 * Step: header navigations and their template parts.
	 *
	 * @return void
	 */
	public function provision_navigations() {
		$theme_dir   = get_theme_root() . '/' . self::THEME_SLUG;
		$header_file = $theme_dir . '/parts/header.html';

		$this->each_site_row(
			'navigations/sites.tsv',
			3,
			'navigation',
			function ( $row ) use ( $header_file ) {
				$role        = $row[0];
				$nav_title   = $row[1];
				$content_rel = 'navigations/' . $row[2];

				$content = $this->apply_urls( $this->read_content( $content_rel ) );

				$nav_id = $this->create_or_update_navigation( $nav_title, 'menu-principal', $content );
				$this->create_or_update_template_part( 'header', 'En-tête', $nav_id, $header_file );
				$this->log( $role . ': header navigation + template part done' );
			}
		);
	}

	/**
	 * Step: footer navigations and their template parts.
	 *
	 * @return void
	 */
	public function provision_footers() {
		$theme_dir   = get_theme_root() . '/' . self::THEME_SLUG;
		$footer_file = $theme_dir . '/parts/footer.html';

		$this->each_site_row(
			'footers/sites.tsv',
			3,
			'footer',
			function ( $row ) use ( $footer_file ) {
				$role         = $row[0];
				$footer_title = $row[1];
				$content_rel  = 'footers/' . $row[2];

				$content = $this->apply_urls( $this->read_content( $content_rel ) );

				$nav_id = $this->create_or_update_navigation( $footer_title, 'footer-principal', $content, false );
				$this->create_or_update_template_part( 'footer', 'Pied de page', $nav_id, $footer_file );
				$this->log( $role . ': footer navigation + template part done' );
			}
		);
	}

	/**
	 * Step: typography test page on the network site.
	 *
	 * @return void
	 */
	public function provision_test_page() {
		$this->ensure_blogs();
		if ( ! isset( $this->blogs['network'] ) ) {
			return;
		}

		foreach ( $this->read_tsv( 'test-pages/page.tsv' ) as $row ) {
			if ( count( $row ) < 10 ) {
				$this->fail( 'Invalid test-page data row: expected 10 columns.' );
			}
			$content = $this->apply_urls( $this->read_content( 'test-pages/' . $row[9] ) );

			$this->with_blog(
				$this->blogs['network'],
				function () use ( $row, $content ) {
					$fields = array(
						'post_type'      => $row[1],
						'post_status'    => $row[2],
						'post_title'     => $row[3],
						'post_name'      => $row[4],
						'post_author'    => (int) $row[5],
						'post_date'      => $row[6],
						'comment_status' => $row[7],
						'ping_status'    => $row[8],
						'post_content'   => $content,
					);
					$this->upsert_page( $fields );
					$this->log( 'Restored test page from ' . $row[9] );
				}
			);
		}
	}

	/**
	 * Step: patterns review page assembled from the pattern registry.
	 *
	 * @return void
	 */
	public function provision_patterns_test_page() {
		$this->ensure_blogs();
		if ( ! isset( $this->blogs['network'] ) ) {
			return;
		}

		foreach ( $this->read_tsv( 'patterns-test-page/page.tsv' ) as $row ) {
			if ( count( $row ) < 7 ) {
				$this->fail( 'Invalid patterns-test-page data row.' );
			}

			$this->with_blog(
				$this->blogs['network'],
				function () use ( $row ) {
					$title       = $row[1];
					$slug        = $row[2];
					$page_content = $this->assemble_patterns_page();

					// Look for the page in any status, including a trashed one
					// (WordPress renames the slug with a __trashed suffix), so a
					// trashed page is restored instead of being duplicated.
					$page_id = $this->find_post_by_name_or_trashed( 'page', $slug );
					if ( $page_id ) {
						// Like the original script, restore the page if it had
						// been trashed, then refresh its content.
						$existing = get_post( $page_id );
						if ( $existing && 'trash' === $existing->post_status && ! wp_untrash_post( $page_id ) ) {
							$this->fail( 'Could not restore the patterns test page: ' . $page_id );
						}
						$updated_id = wp_update_post(
							array(
								'ID'           => $page_id,
								'post_title'   => $title,
								'post_status'  => $row[3],
								'post_content' => $page_content,
							),
							true
						);
						if ( is_wp_error( $updated_id ) ) {
							$this->fail( $updated_id->get_error_message() );
						}
						$action = 'updated';
					} else {
						$page_id = wp_insert_post(
							array(
								'post_type'      => 'page',
								'post_title'     => $title,
								'post_name'      => $slug,
								'post_status'    => $row[3],
								'post_author'    => (int) $row[4],
								'post_content'   => $page_content,
								'comment_status' => $row[5],
								'ping_status'    => $row[6],
							),
							true
						);
						if ( is_wp_error( $page_id ) ) {
							$this->fail( $page_id->get_error_message() );
						}
						$action = 'created';
					}

					$this->log( get_home_url( $this->blogs['network'] ) . ': ' . $action . ' patterns test page ' . $page_id );
				}
			);
		}
	}

	/**
	 * Step: assemble the network Home page from the ordered patterns and the
	 * French copy, then save it. Fails unless the page is safe to overwrite.
	 *
	 * @return void
	 */
	public function provision_home_network() {
		$rows = $this->read_tsv( 'home-network/page.tsv' );
		$this->ensure_blogs();
		if ( ! isset( $this->blogs['network'] ) ) {
			return;
		}
		$network_id    = $this->blogs['network'];
		$pattern_order = $this->read_pattern_order();
		$farm_urls     = $this->farm_site_urls();

		foreach ( $rows as $row ) {
			if ( count( $row ) < 4 ) {
				$this->fail( 'Invalid home-network data row.' );
			}

			$this->with_blog(
				$network_id,
				function () use ( $row, $pattern_order, $farm_urls ) {
					$page_title  = $row[1];
					$page_slug   = $row[2];
					$post_status = $row[3];

					$page_id = $this->find_validated_home_page( $page_title, $page_slug );
					$content = $this->assemble_home_content( $pattern_order, $farm_urls );

					$updated_id = wp_update_post(
						array(
							'ID'           => $page_id,
							'post_content' => $content,
							'post_status'  => $post_status,
						),
						true
					);
					if ( is_wp_error( $updated_id ) ) {
						$this->fail( $updated_id->get_error_message() );
					}

					// update_post_meta() both creates the key when absent and
					// repairs an existing false-valued key to true.
					update_post_meta( $page_id, 'lpu_header_transparent', true );
					$this->log( get_home_url( $this->blogs['network'] ) . ': assembled network Home ' . $page_id );
				}
			);
		}
	}
}
