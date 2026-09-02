<?php
/**
 * LPU Provisioner.
 *
 * One implementation of the Le Paysan Urbain provisioning, runnable both from
 * WP-CLI (`wp lpu provision`) and from a network-admin page (for the shared
 * OVH hosting that has no SSH or WP-CLI). Every method is idempotent:
 * find-or-create, never silently overwrite editorial content.
 *
 * @package Lpu_Provisioning
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Provisioning error, thrown in the HTTP path and turned into a WP-CLI error
 * in the CLI path.
 */
class Lpu_Provision_Error extends Exception {}

/**
 * Core provisioning logic.
 */
class Lpu_Provisioner {

	const THEME_SLUG = 'lepaysanurbain';
	const FARM_ROLES = array( 'paris', 'lyon', 'marseille' );

	/**
	 * Log lines accumulated for HTTP display.
	 *
	 * @var array<int, string>
	 */
	private static $log = array();

	/**
	 * Role => blog_id map, built once by ensure_blogs().
	 *
	 * @var array<string, int>
	 */
	private $blogs = array();

	/**
	 * Whether to replace an already assembled network Home.
	 *
	 * @var bool
	 */
	private $force = false;

	/**
	 * Absolute path to the plugin content directory.
	 *
	 * @var string
	 */
	private $content_dir;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->content_dir = plugin_dir_path( __DIR__ ) . 'content';
	}

	/**
	 * Record a log line for the HTTP path.
	 *
	 * @param string $message Message.
	 * @return void
	 */
	public static function record_log( $message ) {
		self::$log[] = $message;
	}

	/**
	 * Return the accumulated log lines.
	 *
	 * @return array<int, string>
	 */
	public static function get_log() {
		return self::$log;
	}

	/**
	 * Log a line (CLI prints live, HTTP collects).
	 *
	 * @param string $message Message.
	 * @return void
	 */
	public function log( $message ) {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			WP_CLI::log( $message );
		}
		self::record_log( $message );
	}

	/**
	 * Fail the provisioning: CLI exits, HTTP throws.
	 *
	 * @param string $message Message.
	 * @return void
	 * @throws Lpu_Provision_Error In the HTTP path.
	 */
	public function fail( $message ) {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			WP_CLI::error( $message );
		}
		throw new Lpu_Provision_Error( $message );
	}

	/**
	 * Run every provisioning step in dependency order.
	 *
	 * @param bool $force Allow replacing an already assembled network Home.
	 * @return void
	 */
	public function provision( $force = false ) {
		$this->force = (bool) $force;

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
	 * Run `$callback` with the current blog switched to `$blog_id`,
	 * guaranteeing restore even on early return or exception.
	 *
	 * @param int      $blog_id  Blog ID.
	 * @param callable $callback Callable.
	 * @return mixed Callable result.
	 */
	private function with_blog( $blog_id, $callback ) {
		switch_to_blog( $blog_id );
		try {
			return $callback();
		} finally {
			restore_current_blog();
		}
	}

	/**
	 * Read a content file, trimmed of trailing whitespace.
	 *
	 * @param string $rel Path relative to the content directory.
	 * @return string
	 */
	private function read_content( $rel ) {
		$path = $this->content_dir . '/' . $rel;
		if ( ! file_exists( $path ) ) {
			$this->fail( 'Missing content file: ' . $path );
		}
		return trim( (string) file_get_contents( $path ) );
	}

	/**
	 * Parse a pipe or tab separated TSV content file into rows of columns,
	 * skipping blank lines and comments.
	 *
	 * @param string $rel  Path relative to the content directory.
	 * @param string $delim Delimiter (default pipe).
	 * @return array<int, array<int, string>>
	 */
	private function read_tsv( $rel, $delim = '|' ) {
		$text  = $this->read_content( $rel );
		$rows  = array();
		$lines = preg_split( '/\r?\n/', $text );
		foreach ( (array) $lines as $line ) {
			$line = trim( $line );
			if ( '' === $line || '#' === $line[0] ) {
				continue;
			}
			$rows[] = explode( $delim, $line );
		}
		return $rows;
	}

	/**
	 * Ensure every expected blog exists and build the role => blog_id map.
	 *
	 * @return array<string, int>
	 */
	private function ensure_blogs() {
		if ( ! is_multisite() || ! is_subdomain_install() ) {
			$this->fail( 'WordPress is not configured as a subdomain multisite.' );
		}

		$network = get_network();
		if ( ! $network ) {
			$this->fail( 'Could not load the multisite network.' );
		}
		$network_domain = $network->domain;
		$network_path   = (string) $network->path;
		$network_id     = (int) $network->id;
		$main_site_id   = (int) get_main_site_id();

		$this->blogs = array();
		foreach ( get_sites( array( 'number' => 500, 'network_id' => $network_id ) ) as $site ) {
			$blog_id = (int) $site->blog_id;
			if ( $blog_id === $main_site_id ) {
				$this->blogs['network'] = $blog_id;
				continue;
			}
			$host = strtolower( (string) wp_parse_url( get_home_url( $blog_id ), PHP_URL_HOST ) );
			$slug = isset( explode( '.', $host )[0] ) ? explode( '.', $host )[0] : '';
			if ( in_array( $slug, self::FARM_ROLES, true ) ) {
				$this->blogs[ $slug ] = $blog_id;
			}
		}

		$created_any = false;
		foreach ( self::FARM_ROLES as $slug ) {
			if ( isset( $this->blogs[ $slug ] ) ) {
				continue;
			}
			$this->log( 'Creating sub-site: ' . $slug );
			$blog_id = wpmu_create_blog(
				$slug . '.' . $network_domain,
				$network_path,
				'Le Paysan Urbain ' . ucfirst( $slug ),
				1,
				array(),
				$network_id
			);
			if ( is_wp_error( $blog_id ) ) {
				$this->fail( 'Could not create sub-site ' . $slug . ': ' . $blog_id->get_error_message() );
			}
			clean_site_cache( (int) $blog_id );
			$this->blogs[ $slug ] = (int) $blog_id;
			$created_any          = true;
		}

		// wp-env installs the network before applying SUBDOMAIN_INSTALL. Keep
		// the network metadata aligned with the subdomain choice.
		update_site_option( 'subdomain_install', 1 );

		// Name the network site (a fresh install defaults to "WordPress").
		$this->with_blog(
			$main_site_id,
			function () {
				if ( 'Le Paysan Urbain' !== (string) get_option( 'blogname' ) ) {
					update_option( 'blogname', 'Le Paysan Urbain' );
				}
			}
		);

		if ( $created_any ) {
			$this->log( 'Created missing sub-sites.' );
		}
		$this->log( 'Network sites: ' . implode( ', ', array_keys( $this->blogs ) ) );
		return $this->blogs;
	}

	/**
	 * Step: multisite network and sub-sites.
	 *
	 * @return void
	 */
	public function provision_network_sites() {
		$this->ensure_blogs();
	}

	/**
	 * Resolve the placeholder => URL map for the content fragments.
	 *
	 * @return array<string, string>
	 */
	private function resolve_urls() {
		$blogs = $this->ensure_blogs();
		$map   = array();

		foreach ( array( 'network', 'paris', 'lyon', 'marseille' ) as $role ) {
			if ( ! isset( $blogs[ $role ] ) ) {
				continue;
			}
			$url = get_home_url( $blogs[ $role ] );
			if ( 'network' === $role ) {
				$map['{{NETWORK_URL}}'] = trailingslashit( $url );
			} else {
				$map[ '{{FARM_' . strtoupper( $role ) . '_URL}}' ] = trailingslashit( $url );
			}
		}

		return $map;
	}

	/**
	 * Replace URL placeholder tokens in a content fragment.
	 *
	 * @param string $content Content.
	 * @return string
	 */
	private function apply_urls( $content ) {
		$map = $this->resolve_urls();
		return str_replace( array_keys( $map ), array_values( $map ), $content );
	}

	/**
	 * Step: enable and activate the theme.
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
	 * Step: set the French locale on every site and the admin user.
	 *
	 * The core fr_FR language pack itself cannot be installed from PHP; it is
	 * installed by WP-CLI locally (`wp language core install`) and manually in
	 * wp-admin on the OVH target. This step only records the locale choice.
	 *
	 * @return void
	 */
	public function provision_language() {
		$locale = 'fr_FR';
		$this->ensure_blogs();
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

		// The default account is named "admin" (wp-env default).
		$admin = get_user_by( 'login', 'admin' );
		if ( $admin ) {
			// Network-wide so the admin UI is French on every site.
			update_user_option( $admin->ID, 'locale', $locale, true );
			$this->log( 'admin: user locale ' . $locale );
		}
	}

	/**
	 * Import an attachment file into the media library of a blog, or return
	 * the existing attachment for the same filename.
	 *
	 * Runs in the target blog context.
	 *
	 * @param string $file_path Absolute filesystem path.
	 * @param string $title     Attachment title.
	 * @return int
	 */
	private function import_attachment( $file_path, $title ) {
		if ( ! file_exists( $file_path ) ) {
			$this->fail( 'Missing asset: ' . $file_path );
		}

		$filename = basename( $file_path );

		// Reuse an existing attachment for the same filename.
		$existing = get_posts(
			array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'post_mime_type' => 'image/svg+xml',
				'meta_key'       => '_wp_attached_file',
				'meta_value'     => '/' . $filename,
				'meta_compare'   => 'LIKE',
				'posts_per_page' => 1,
				'orderby'        => 'ID',
				'order'          => 'DESC',
				'fields'         => 'ids',
			)
		);
		if ( $existing ) {
			return (int) $existing[0];
		}

		$uploaded = wp_upload_bits( $filename, null, (string) file_get_contents( $file_path ) );
		if ( ! empty( $uploaded['error'] ) ) {
			$this->fail( 'Could not write ' . $filename . ': ' . $uploaded['error'] );
		}

		// SVG passes core's filetype check only when a filter allows it. The
		// theme registers that filter, but its functions.php is not loaded in
		// this request if the theme is only switched mid-run, so allow SVG here.
		$ext = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );
		if ( 'svg' === $ext ) {
			$filetype = array(
				'ext'  => 'svg',
				'type' => 'image/svg+xml',
			);
		} else {
			$filetype = wp_check_filetype_and_ext( $uploaded['file'], $filename );
			if ( empty( $filetype['type'] ) ) {
				$this->fail( 'Unsupported upload type for ' . $filename );
			}
		}

		$attach_id = wp_insert_attachment(
			array(
				'post_mime_type' => $filetype['type'],
				'post_title'     => $title,
				'post_content'   => '',
				'post_status'    => 'inherit',
			),
			$uploaded['file'],
			0,
			true
		);
		if ( is_wp_error( $attach_id ) ) {
			$this->fail( 'Could not attach ' . $filename . ': ' . $attach_id->get_error_message() );
		}

		$metadata = wp_generate_attachment_metadata( $attach_id, $uploaded['file'] );
		wp_update_attachment_metadata( $attach_id, $metadata );

		return (int) $attach_id;
	}

	/**
	 * Step: import and select the per-site logos.
	 *
	 * @return void
	 */
	public function provision_logos() {
		$logos_dir = get_theme_root() . '/' . self::THEME_SLUG . '/assets/images/logos';
		$rows      = $this->read_tsv( 'logos-sites.tsv' );
		$this->ensure_blogs();

		foreach ( $rows as $row ) {
			if ( count( $row ) < 5 ) {
				$this->fail( 'Invalid logo data row: expected 5 pipe-separated columns.' );
			}
			$role                     = $row[0];
			$logo_title               = $row[1];
			$logo_file                = $row[2];
			$transparent_logo_title   = $row[3];
			$transparent_logo_file    = $row[4];

			if ( ! isset( $this->blogs[ $role ] ) ) {
				continue;
			}
			$blog_id = $this->blogs[ $role ];

			$this->with_blog(
				$blog_id,
				function () use ( $logos_dir, $logo_title, $logo_file, $transparent_logo_title, $transparent_logo_file, $role ) {
					$logo_id = $this->import_attachment( $logos_dir . '/' . $logo_file, $logo_title );
					set_theme_mod( 'custom_logo', $logo_id );

					$transparent_logo_id = $this->import_attachment( $logos_dir . '/' . $transparent_logo_file, $transparent_logo_title );
					set_theme_mod( 'lpu_transparent_logo', $transparent_logo_id );

					$this->log( $role . ': custom_logo ' . $logo_id . ' (' . $logo_file . '), transparent ' . $transparent_logo_id );
				}
			);
		}
	}

	/**
	 * Find the ID of a post by name within the current blog.
	 *
	 * @param string $post_type Post type.
	 * @param string $name      Post name.
	 * @return int 0 when none.
	 */
	/**
	 * Find the ID of a post by name within the current blog.
	 *
	 * The caller is responsible for having switched to the target blog.
	 *
	 * @param string $post_type Post type.
	 * @param string $name      Post name.
	 * @return int 0 when none.
	 */
	private function find_post_by_name( $post_type, $name ) {
		$posts = get_posts(
			array(
				'post_type'      => $post_type,
				'post_status'    => 'any',
				'name'           => $name,
				'posts_per_page' => 1,
				'fields'         => 'ids',
			)
		);
		return $posts ? (int) $posts[0] : 0;
	}

	/**
	 * Step: create or select the static front page on every site.
	 *
	 * @return void
	 */
	public function provision_front_pages() {
		$page_content = $this->read_content( 'accueil.html' );
		$rows         = $this->read_tsv( 'sites.tsv' );
		$this->ensure_blogs();

		foreach ( $rows as $row ) {
			if ( count( $row ) < 3 ) {
				$this->fail( 'Invalid front-page data row: expected 3 columns.' );
			}
			$role       = $row[0];
			$page_title = $row[1];
			$page_slug  = $row[2];
			if ( ! isset( $this->blogs[ $role ] ) ) {
				continue;
			}
			$blog_id = $this->blogs[ $role ];

			$this->with_blog(
				$blog_id,
				function () use ( $role, $page_title, $page_slug, $page_content ) {
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
	}

	/**
	 * Set the theme/area taxonomy terms and origin meta on a navigation or
	 * template-part post, then flush the caches WordPress keeps for these.
	 *
	 * Runs in the current blog context.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $area    Template part area ('' for navigation).
	 * @return void
	 */
	private function tag_template_element( $post_id, $area ) {
		wp_set_post_terms( $post_id, self::THEME_SLUG, 'wp_theme', false );
		if ( '' !== $area ) {
			wp_set_post_terms( $post_id, $area, 'wp_template_part_area', false );
		}
		update_post_meta( $post_id, 'origin', 'theme' );
		clean_post_cache( $post_id );
	}

	/**
	 * Create or update a wp_navigation post from a content fragment.
	 *
	 * The header menu is refreshed on every run so it always matches the
	 * current fragment file. Footers pass $update_existing=false so an
	 * existing footer navigation is created once and left untouched, letting
	 * editorial edits persist (note: the footer template part still refreshes).
	 *
	 * @param string $title           Navigation title.
	 * @param string $navigation_name Post name.
	 * @param string $content         Navigation block content.
	 * @param bool   $update_existing Whether to refresh an existing post.
	 * @return int Navigation post ID.
	 */
	private function create_or_update_navigation( $title, $navigation_name, $content, $update_existing = true ) {
		$nav_id = $this->find_post_by_name( 'wp_navigation', $navigation_name );
		if ( $nav_id ) {
			if ( $update_existing ) {
				wp_update_post(
					array(
						'ID'           => $nav_id,
						'post_title'   => $title,
						'post_content' => $content,
					)
				);
				$this->log( 'Updated navigation ' . $navigation_name . ' (' . $nav_id . ')' );
			}
		} else {
			$nav_id = wp_insert_post(
				array(
					'post_type'    => 'wp_navigation',
					'post_status'  => 'publish',
					'post_title'   => $title,
					'post_name'    => $navigation_name,
					'post_content' => $content,
				),
				true
			);
			if ( is_wp_error( $nav_id ) ) {
				$this->fail( $nav_id->get_error_message() );
			}
			$this->log( 'Created navigation ' . $navigation_name . ' (' . $nav_id . ')' );
		}
		$this->tag_template_element( (int) $nav_id, '' );
		return (int) $nav_id;
	}

	/**
	 * Create or update the site-local template-part override for a given part
	 * name (header/footer), injecting a navigation ref into its content.
	 *
	 * @param string $part_name     Template part post name (header/footer).
	 * @param string $part_title    Human title.
	 * @param int    $navigation_id Navigation ID to reference.
	 * @param string $template_file Full path to the theme template part file.
	 * @return int Template part post ID.
	 */
	private function create_or_update_template_part( $part_name, $part_title, $navigation_id, $template_file ) {
		$template_content = (string) file_get_contents( $template_file );
		$template_content = preg_replace(
			'/(<!-- wp:navigation \{)/',
			'$1"ref":' . (int) $navigation_id . ',',
			$template_content,
			1
		);

		$part_id = $this->find_post_by_name( 'wp_template_part', $part_name );
		if ( $part_id ) {
			wp_update_post(
				array(
					'ID'           => $part_id,
					'post_content' => $template_content,
				)
			);
			$this->log( 'Updated template part ' . $part_name . ' (' . $part_id . ')' );
		} else {
			$part_id = wp_insert_post(
				array(
					'post_type'    => 'wp_template_part',
					'post_status'  => 'publish',
					'post_title'   => $part_title,
					'post_name'    => $part_name,
					'post_content' => $template_content,
				),
				true
			);
			if ( is_wp_error( $part_id ) ) {
				$this->fail( $part_id->get_error_message() );
			}
			$this->log( 'Created template part ' . $part_name . ' (' . $part_id . ')' );
		}

		$area    = ( 'header' === $part_name ) ? 'header' : 'footer';
		$this->tag_template_element( (int) $part_id, $area );

		return (int) $part_id;
	}

	/**
	 * Step: header navigations and their template parts.
	 *
	 * @return void
	 */
	public function provision_navigations() {
		$theme_dir = get_theme_root() . '/' . self::THEME_SLUG;
		$header_file = $theme_dir . '/parts/header.html';
		$rows        = $this->read_tsv( 'navigations/sites.tsv' );
		$this->ensure_blogs();

		foreach ( $rows as $row ) {
			if ( count( $row ) < 3 ) {
				$this->fail( 'Invalid navigation data row: expected 3 columns.' );
			}
			$role        = $row[0];
			$nav_title   = $row[1];
			$content_rel = 'navigations/' . $row[2];
			if ( ! isset( $this->blogs[ $role ] ) ) {
				continue;
			}
			$content = $this->apply_urls( $this->read_content( $content_rel ) );

			$this->with_blog(
				$this->blogs[ $role ],
				function () use ( $role, $nav_title, $content, $header_file ) {
					$nav_id = $this->create_or_update_navigation( $nav_title, 'menu-principal', $content );
					$this->create_or_update_template_part( 'header', 'En-tête', $nav_id, $header_file );
					$this->log( $role . ': header navigation + template part done' );
				}
			);
		}
	}

	/**
	 * Step: footer navigations and their template parts.
	 *
	 * @return void
	 */
	public function provision_footers() {
		$theme_dir = get_theme_root() . '/' . self::THEME_SLUG;
		$footer_file = $theme_dir . '/parts/footer.html';
		$rows        = $this->read_tsv( 'footers/sites.tsv' );
		$this->ensure_blogs();

		foreach ( $rows as $row ) {
			if ( count( $row ) < 3 ) {
				$this->fail( 'Invalid footer data row: expected 3 columns.' );
			}
			$role        = $row[0];
			$footer_title = $row[1];
			$content_rel = 'footers/' . $row[2];
			if ( ! isset( $this->blogs[ $role ] ) ) {
				continue;
			}
			$content = $this->apply_urls( $this->read_content( $content_rel ) );

			$this->with_blog(
				$this->blogs[ $role ],
				function () use ( $role, $footer_title, $content, $footer_file ) {
					$nav_id = $this->create_or_update_navigation( $footer_title, 'footer-principal', $content, false );
					$this->create_or_update_template_part( 'footer', 'Pied de page', $nav_id, $footer_file );
					$this->log( $role . ': footer navigation + template part done' );
				}
			);
		}
	}

	/**
	 * Create or update a single page on the network site.
	 *
	 * @param array<string, string> $fields Post fields inclined in content_* keys.
	 * @param array<string, string> $content_meta Keys whose value is raw content.
	 * @return int
	 */
	private function upsert_page( $fields ) {
		$page_id = $this->find_post_by_name( 'page', $fields['post_name'] );
		if ( $page_id ) {
			// Like the original scripts, an existing page is left untouched so
			// editorial edits are never clobbered.
			return (int) $page_id;
		}

		$fields = array_merge(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_author'  => 1,
				'comment_status' => 'closed',
				'ping_status'  => 'closed',
			),
			$fields
		);
		$page_id = wp_insert_post( $fields, true );
		if ( is_wp_error( $page_id ) ) {
			$this->fail( $page_id->get_error_message() );
		}
		return (int) $page_id;
	}

	/**
	 * Step: typography test page on the network site.
	 *
	 * @return void
	 */
	public function provision_test_page() {
		$rows = $this->read_tsv( 'test-pages/page.tsv', '|' );
		$this->ensure_blogs();
		if ( ! isset( $this->blogs['network'] ) ) {
			return;
		}

		foreach ( $rows as $row ) {
			if ( count( $row ) < 10 ) {
				$this->fail( 'Invalid test-page data row: expected 10 columns.' );
			}
			$content = $this->apply_urls( $this->read_content( 'test-pages/' . $row[9] ) );
			$this->with_blog(
				$this->blogs['network'],
				function () use ( $row, $content ) {
					$fields = array(
						'post_type'    => $row[1],
						'post_status'  => $row[2],
						'post_title'   => $row[3],
						'post_name'    => $row[4],
						'post_author'  => (int) $row[5],
						'post_date'    => $row[6],
						'comment_status' => $row[7],
						'ping_status'  => $row[8],
						'post_content' => $content,
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
		$rows = $this->read_tsv( 'patterns-test-page/page.tsv', '|' );
		$this->ensure_blogs();
		if ( ! isset( $this->blogs['network'] ) ) {
			return;
		}

		foreach ( $rows as $row ) {
			if ( count( $row ) < 7 ) {
				$this->fail( 'Invalid patterns-test-page data row.' );
			}
			$this->with_blog(
				$this->blogs['network'],
				function () use ( $row ) {
					$title = $row[1];
					$slug  = $row[2];
					$page_content = $this->assemble_patterns_page();

					$page_id = $this->find_post_by_name( 'page', $slug );
					if ( $page_id ) {
						wp_update_post(
							array(
								'ID'           => $page_id,
								'post_title'   => $title,
								'post_content' => $page_content,
							)
						);
						$action = 'updated';
					} else {
						$page_id = wp_insert_post(
							array(
								'post_type'     => 'page',
								'post_title'    => $title,
								'post_name'     => $slug,
								'post_status'   => $row[3],
								'post_author'   => (int) $row[4],
								'post_content'  => $page_content,
								'comment_status'=> $row[5],
								'ping_status'   => $row[6],
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
	 * Build the patterns review page content from the pattern registry.
	 *
	 * @return string
	 */
	private function assemble_patterns_page() {
		$split_section_namespace = 'lpu-split-section/';
		$theme_namespace         = trailingslashit( get_stylesheet() );
		$patterns                = array();

		foreach ( WP_Block_Patterns_Registry::get_instance()->get_all_registered() as $pattern ) {
			if ( ! isset( $pattern['name'], $pattern['content'] ) ) {
				continue;
			}
			$source = isset( $pattern['source'] ) ? (string) $pattern['source'] : '';
			$is_theme = 'theme' === $source || 0 === strpos( (string) $pattern['name'], $theme_namespace );
			$is_split = 0 === strpos( (string) $pattern['name'], $split_section_namespace );
			if ( $is_theme || $is_split ) {
				$patterns[ $pattern['name'] ] = $pattern;
			}
		}

		ksort( $patterns, SORT_NATURAL | SORT_FLAG_CASE );
		if ( ! $patterns ) {
			$this->fail( 'No patterns provided by the active theme or LPU split-section plugin.' );
		}

		$content = '';
		foreach ( $patterns as $pattern ) {
			$content .= $this->pattern_with_metadata( $pattern['content'], $pattern ) . "\n";
		}
		return $content;
	}

	/**
	 * Stamp pattern metadata onto the first block of a pattern, like the
	 * editor does for inserted patterns.
	 *
	 * @param string $content Pattern content.
	 * @param array  $pattern Pattern definition.
	 * @return string
	 */
	private function pattern_with_metadata( $content, $pattern ) {
		$blocks = parse_blocks( $content );
		if ( ! isset( $blocks[0]['blockName'] ) || '' === $blocks[0]['blockName'] ) {
			$this->fail( 'Pattern content does not start with a block: ' . ( $pattern['name'] ?? 'unknown' ) );
		}

		$blocks[0]['attrs']['metadata'] = array(
			'categories'  => array_values( $pattern['categories'] ?? array() ),
			'patternName' => $pattern['name'],
			'name'        => $pattern['title'],
		);

		return serialize_blocks( $blocks );
	}

	/**
	 * Step: assemble the network Home from the theme patterns.
	 *
	 * @return void
	 */
	public function provision_home_network() {
		$rows = $this->read_tsv( 'home-network/page.tsv', '|' );
		$this->ensure_blogs();
		if ( ! isset( $this->blogs['network'] ) ) {
			return;
		}

		$order      = $this->read_content( 'home-network/home-sections-names.txt' );
		$pattern_order = array();
		foreach ( preg_split( '/\r?\n/', $order ) as $line ) {
			$line = trim( $line );
			if ( '' === $line || '#' === $line[0] ) {
				continue;
			}
			if ( false !== strpos( $line, "'" ) ) {
				$this->fail( 'Pattern name contains an unsupported quote: ' . $line );
			}
			$pattern_order[] = $line;
		}
		if ( ! $pattern_order ) {
			$this->fail( 'No patterns declared in home-sections-names.txt.' );
		}

		foreach ( $rows as $row ) {
			if ( count( $row ) < 4 ) {
				$this->fail( 'Invalid home-network data row.' );
			}
			$this->with_blog(
				$this->blogs['network'],
				function () use ( $row, $pattern_order ) {
					$page_title  = $row[1];
					$page_slug   = $row[2];
					$post_status = $row[3];

					$pages = get_posts(
						array(
							'post_type'      => 'page',
							'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future', 'trash' ),
							'name'           => $page_slug,
							'posts_per_page' => 2,
							'orderby'        => 'ID',
							'order'          => 'ASC',
							'fields'         => 'ids',
						)
					);
					if ( count( $pages ) > 1 ) {
						$this->fail( 'More than one page uses the Home slug: ' . $page_slug );
					}
					if ( ! $pages ) {
						$this->fail( 'The technical front page does not exist (' . $page_slug . '). Run front pages first.' );
					}

					$page_id = (int) $pages[0];
					$page    = get_post( $page_id );
					if ( 'trash' === $page->post_status && ! wp_untrash_post( $page_id ) ) {
						$this->fail( 'Could not restore the network Home page: ' . $page_id );
					}
					if ( (string) $page->post_title !== $page_title ) {
						$this->fail( 'The page title is not the expected Home title: ' . $page->post_title );
					}
					if ( 'page' !== get_option( 'show_on_front' ) || (int) get_option( 'page_on_front' ) !== $page_id ) {
						$this->fail( 'The expected Home page is not the current page_on_front. Run front pages first.' );
					}

					$technical_placeholder = '<!--
  This page intentionally starts without visible content.
  Home sections will be assembled in Gutenberg from the theme patterns.
-->';
					if ( ! $this->force && trim( (string) $page->post_content ) !== trim( $technical_placeholder ) ) {
						$this->fail( 'The Home already contains editorial content. Re-run with --force only when replacement is intentional.' );
					}

					$patterns_by_name = array();
					foreach ( WP_Block_Patterns_Registry::get_instance()->get_all_registered() as $pattern ) {
						if ( isset( $pattern['name'], $pattern['content'] ) ) {
							$patterns_by_name[ $pattern['name'] ] = $pattern;
						}
					}

					$farm_urls = array();
					$farm_labels = array(
						'paris'     => 'Paris',
						'lyon'      => 'Lyon',
						'marseille' => 'Marseille',
					);
					foreach ( get_sites( array( 'number' => 100, 'network_id' => get_current_network_id() ) ) as $site ) {
						$site_home = trailingslashit( get_home_url( (int) $site->blog_id ) );
						$host      = strtolower( (string) wp_parse_url( $site_home, PHP_URL_HOST ) );
						$host_parts = explode( '.', $host );
						$site_key   = $host_parts[0] ?? '';
						if ( isset( $farm_labels[ $site_key ] ) ) {
							$farm_urls[ $farm_labels[ $site_key ] ] = $site_home;
						}
					}
					foreach ( $farm_labels as $label ) {
						if ( ! isset( $farm_urls[ $label ] ) ) {
							$this->fail( 'Could not resolve the multisite URL for farm: ' . $label );
						}
					}

					$page_content     = '';
					$cards_occurrences = 0;
					foreach ( $pattern_order as $pattern_name ) {
						if ( ! isset( $patterns_by_name[ $pattern_name ] ) ) {
							$this->fail( 'Active theme pattern is missing: ' . $pattern_name );
						}
						$pattern = $patterns_by_name[ $pattern_name ];
						$section = $pattern['content'];

						if ( 'lepaysanurbain/hero' === $pattern_name ) {
							$section = $this->home_text( $section, 'Titre principal de la page', 'Cultiver le vivant en ville.', 'Home hero title' );
							$section = $this->home_text( $section, 'Présentez ici le sujet principal de la page en quelques mots.', 'Présentez ici la promesse de cette page et le rôle du Paysan Urbain dans la ville.', 'Home hero text' );
						}

						if ( 'lpu-split-section/split-content-image' === $pattern_name ) {
							$section = $this->home_text( $section, 'Sur-titre', 'Une histoire à raconter', 'Home split content eyebrow' );
							$section = $this->home_text( $section, 'Un titre qui tient dans sa moitié', 'Présentez votre action sur deux lignes', 'Home split content title' );
							$section = $this->home_text( $section, 'Ajoutez ici le texte, les informations et les appels à l’action propres à cette zone.', 'Ajoutez ici quelques lignes pour expliquer le projet, son utilité et la manière dont le visiteur peut y prendre part.', 'Home split content text' );
						}

						if ( 'lepaysanurbain/network-farm-selector' === $pattern_name ) {
							$farm_placeholders = array(
								'Paris'     => 'Ferme 1',
								'Lyon'      => 'Ferme 2',
								'Marseille' => 'Ferme 3',
							);
							foreach ( $farm_labels as $label ) {
								$needle      = '<a>' . $farm_placeholders[ $label ] . '</a>';
								$replacement = '<a href="' . esc_url( $farm_urls[ $label ] ) . '">' . $label . '</a>';
								$section     = str_replace( $needle, $replacement, $section, $link_count );
								if ( 1 !== $link_count ) {
									$this->fail( 'Expected one unconfigured farm link for ' . $label );
								}
							}
						}

						if ( 'lepaysanurbain/cards' === $pattern_name ) {
							$cards_occurrences++;
							$section = $this->home_text( $section, 'Titre de la grille', 1 === $cards_occurrences ? 'Des façons d’agir' : 'Le réseau en action', 'Home cards title ' . $cards_occurrences );

							$card_titles = 1 === $cards_occurrences
								? array( 'Particuliers', 'Professionnels', 'Partenaires et institutions' )
								: array( 'Activités et événements', 'Production locale', 'Projets et insertion' );
							$generic_card_titles = array( 'Titre de carte 1', 'Titre de carte 2', 'Titre de carte 3' );
							foreach ( $generic_card_titles as $index => $generic_title ) {
								$section = $this->home_text( $section, $generic_title, $card_titles[ $index ], 'Home card title ' . ( $index + 1 ) );
							}

							if ( 2 === $cards_occurrences ) {
								$section = str_replace(
									'"backgroundColor":"ecru","className":"lpu-band lpu-card-grid lpu-motif lpu-motif-1-bandeau"',
									'"backgroundColor":"vert-grise","className":"lpu-band lpu-card-grid lpu-card-grid--titles-only"',
									$section,
									$outer_attribute_count
								);
								if ( 1 !== $outer_attribute_count ) {
									$this->fail( 'Expected the second cards pattern wrapper attributes.' );
								}
								$section = str_replace(
									'lpu-card-grid lpu-motif lpu-motif-1-bandeau has-ecru-background-color has-background',
									'lpu-card-grid lpu-card-grid--titles-only has-vert-grise-background-color has-background',
									$section,
									$outer_class_count
								);
								if ( 1 !== $outer_class_count ) {
									$this->fail( 'Expected the second cards pattern wrapper classes.' );
								}
								$section = preg_replace( '/\s*<!-- wp:paragraph\b.*?<!-- \/wp:paragraph -->/s', '', $section, -1, $paragraph_count );
								if ( null === $section || 3 !== $paragraph_count ) {
									$this->fail( 'Expected three optional card descriptions in the second cards pattern.' );
								}
								$section = preg_replace( '/\s*<!-- wp:buttons\b.*?<!-- \/wp:buttons -->/s', '', $section, -1, $button_count );
								if ( null === $section || 3 !== $button_count ) {
									$this->fail( 'Expected three optional card buttons in the second cards pattern.' );
								}
							} else {
								$section = str_replace( '>En savoir plus<', '>Découvrir<', $section, $button_label_count );
								if ( 3 !== $button_label_count ) {
									$this->fail( 'Expected three generic card button labels in the first cards pattern.' );
								}
								$card_descriptions = array(
									'Décrivez brièvement le contenu de cette carte et son intérêt pour vos visiteurs.' => 'Visiter, participer, découvrir.',
									'Ajoutez une information courte sur cette proposition.' => 'Commander des produits locaux.',
									'Présentez un troisième contenu ou une action à découvrir.' => 'Soutenir, collaborer, développer des projets.',
								);
								foreach ( $card_descriptions as $generic_text => $home_text ) {
									$section = $this->home_text( $section, $generic_text, $home_text, 'Home card description' );
								}
							}
						}

						if ( 'lepaysanurbain/columns' === $pattern_name ) {
							$section = $this->home_text( $section, 'Titre commun', 'Un message commun à faire vivre', 'Home columns title' );
							$section = $this->home_text( $section, 'Premier message à présenter dans cette colonne.', 'Présentez ici un premier message court, une information ou une valeur importante du projet.', 'Home columns text 1' );
							$section = $this->home_text( $section, 'Deuxième message à présenter dans cette colonne.', 'Utilisez cette colonne pour compléter le propos avec un deuxième message lisible et autonome.', 'Home columns text 2' );
							$section = $this->home_text( $section, 'Troisième message à présenter dans cette colonne.', 'Ajoutez un dernier repère, un chiffre ou un lien vers une information complémentaire.', 'Home columns text 3' );
						}

						if ( 'lpu-split-section/split-motif-image' === $pattern_name ) {
							$section = $this->home_text( $section, 'Sur-titre', 'Une ferme, des savoir-faire', 'Home motif eyebrow' );
							$section = $this->home_text( $section, 'Titre de la mise en avant', 'Cultiver et transmettre au quotidien', 'Home motif title' );
							$section = $this->home_text( $section, 'Présentez ici le contenu de cette mise en avant.', 'Décrivez ici l’action mise en avant, les personnes concernées et la manière dont cette initiative fait grandir le vivant en ville.', 'Home motif text' );
						}

						if ( 'lepaysanurbain/graphic-band' === $pattern_name ) {
							$section = $this->home_text( $section, 'Titre de l’appel à l’action', 'Prêt à cultiver le vivant avec nous&nbsp;?', 'Home graphic title' );
							$section = $this->home_text( $section, 'Ajoutez ici une phrase courte pour guider vos visiteurs.', 'Rassemblez ici les dernières informations utiles et invitez vos visiteurs à passer à l’action.', 'Home graphic text' );
						}

						$page_content .= $this->pattern_with_metadata( $section, $pattern ) . "\n";
					}

					if ( 2 !== $cards_occurrences ) {
						$this->fail( 'The Home order must contain exactly two cards patterns.' );
					}

					$updated_id = wp_update_post(
						array(
							'ID'           => $page_id,
							'post_content' => $page_content,
							'post_status'  => $post_status,
						),
						true
					);
					if ( is_wp_error( $updated_id ) ) {
						$this->fail( $updated_id->get_error_message() );
					}

					if ( ! get_post_meta( $page_id, 'lpu_header_transparent', true ) ) {
						add_post_meta( $page_id, 'lpu_header_transparent', true, true );
					}
					$this->log( get_home_url( $this->blogs['network'] ) . ': assembled network Home ' . $page_id );
				}
			);
		}
	}

	/**
	 * Replace a single text slot in a content fragment, asserting exactly one
	 * occurrence.
	 *
	 * @param string $content Content.
	 * @param string $from    Search.
	 * @param string $to      Replacement.
	 * @param string $label   Slot description for the assertion error.
	 * @return string
	 */
	private function home_text( $content, $from, $to, $label ) {
		$count   = 0;
		$result  = str_replace( '>' . $from . '<', '>' . $to . '<', $content, $count );
		if ( 1 !== $count ) {
			$this->fail( 'Expected one Home content slot for ' . $label . ', found ' . $count );
		}
		return $result;
	}
}
