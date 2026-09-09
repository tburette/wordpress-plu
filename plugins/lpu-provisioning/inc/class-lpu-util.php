<?php

/**
 * LPU Provisioning — internal machinery.
 *
 * This trait holds all the "complicated" plumbing used by the provisioning
 * steps: reading content/TSV files, switching blog context, importing media,
 * creating navigation/template parts, assembling pages from patterns, and
 * logging/failing. The step methods themselves (in class-lpu-provisioner.php)
 * stay short and read top-to-bottom like the original shell scripts.
 *
 * @package Lpu_Provisioning
 */

if (! defined('ABSPATH')) {
	exit;
}

/**
 * Provisioning error. Thrown in the HTTP path (the CLI path prints and exits
 * through WP_CLI::error instead).
 */
class Lpu_Provision_Error extends Exception {}

/**
 * Shared machinery for the provisioning steps.
 */
trait Lpu_Util
{

	/**
	 * Slug of the theme this provisioning targets.
	 *
	 * @var string
	 */
	const THEME_SLUG = 'lepaysanurbain';

	/**
	 * Sub-site roles (the sub-domain prefix of each farm site).
	 *
	 * @var array<int, string>
	 */
	const FARM_ROLES = array('paris', 'lyon', 'marseille');

	/**
	 * Log lines accumulated for the HTTP (wp-admin) display.
	 *
	 * @var array<int, string>
	 */
	private static $log = array();

	/**
	 * role => blog_id map, built once by ensure_blogs().
	 *
	 * @var array<string, int>
	 */
	protected $blogs = array();

	/**
	 * Absolute path to the plugin content directory.
	 *
	 * @var string
	 */
	protected $content_dir;

	/**
	 * Constructor.
	 */
	public function __construct()
	{
		$this->content_dir = plugin_dir_path(__DIR__) . 'content';
	}

	/**
	 * Record a log line (used by the HTTP display).
	 *
	 * @param string $message Message.
	 * @return void
	 */
	public static function record_log($message)
	{
		self::$log[] = $message;
	}

	/**
	 * Return the accumulated log lines.
	 *
	 * @return array<int, string>
	 */
	public static function get_log()
	{
		return self::$log;
	}

	/**
	 * Log a line (CLI prints it live, HTTP collects it).
	 *
	 * @param string $message Message.
	 * @return void
	 */
	protected function log($message)
	{
		if (defined('WP_CLI') && WP_CLI) {
			WP_CLI::log($message);
		}
		self::record_log($message);
	}

	/**
	 * Fail the provisioning: CLI exits, HTTP throws.
	 *
	 * @param string $message Message.
	 * @return void
	 * @throws Lpu_Provision_Error In the HTTP path.
	 */
	protected function fail($message)
	{
		if (defined('WP_CLI') && WP_CLI) {
			WP_CLI::error($message);
		}
		throw new Lpu_Provision_Error($message);
	}

	/**
	 * Run $callback with the current site switched to $blog_id, restoring the
	 * previous site even on early return or exception.
	 *
	 * @param int      $blog_id  Blog ID.
	 * @param callable $callback Callable.
	 * @return mixed Callable result.
	 */
	protected function with_blog($blog_id, $callback)
	{
		switch_to_blog($blog_id);
		try {
			return $callback();
		} finally {
			restore_current_blog();
		}
	}

	/**
	 * Read a content file, trimmed of surrounding whitespace.
	 *
	 * @param string $rel Path relative to the content directory.
	 * @return string
	 */
	protected function read_content($rel)
	{
		$path = $this->content_dir . '/' . $rel;
		if (! file_exists($path)) {
			$this->fail('Missing content file: ' . $path);
		}
		return trim((string) file_get_contents($path));
	}

	/**
	 * Parse a pipe-delimited TSV content file into rows of columns, skipping
	 * blank lines and comment (#) lines.
	 *
	 * @param string $rel Path relative to the content directory.
	 * @return array<int, array<int, string>>
	 */
	protected function read_tsv($rel)
	{
		$text  = $this->read_content($rel);
		$rows  = array();
		$lines = preg_split('/\r?\n/', $text);
		foreach ((array) $lines as $line) {
			$line = trim($line);
			if ('' === $line || '#' === $line[0]) {
				continue;
			}
			$rows[] = explode('|', $line);
		}
		return $rows;
	}

	/**
	 * Iterate the rows of a role-keyed TSV, running $callback on each row's own
	 * site (the site is switched-to for the duration of the callback).
	 *
	 * In plain terms: for each line of the file, "go to that site and do X".
	 * Rows whose role has no site are skipped.
	 *
	 * @param string   $rel       TSV path relative to the content directory.
	 * @param int      $min_cols  Minimum columns each row must have.
	 * @param string   $err_label Label describing the step (for the error text).
	 * @param callable $callback  fn( $row, $role, $blog_id ) — runs on the site.
	 * @return void
	 */
	protected function each_site_row($rel, $min_cols, $err_label, $callback)
	{
		$rows = $this->read_tsv($rel);
		$this->ensure_blogs();

		foreach ($rows as $row) {
			if (count($row) < $min_cols) {
				$this->fail('Invalid ' . $err_label . ' data row: expected ' . $min_cols . ' columns.');
			}
			$role = $row[0];
			if (! isset($this->blogs[$role])) {
				continue;
			}
			$blog_id = $this->blogs[$role];
			$this->with_blog(
				$blog_id,
				function () use ($row, $role, $blog_id, $callback) {
					$callback($row, $role, $blog_id);
				}
			);
		}
	}

	/**
	 * Ensure every expected site exists and build the role => blog_id map.
	 *
	 * @return array<string, int>
	 */
	protected function ensure_blogs()
	{
		if (! is_multisite() || ! is_subdomain_install()) {
			$this->fail('WordPress is not configured as a subdomain multisite.');
		}

		$network = get_network();
		if (! $network) {
			$this->fail('Could not load the multisite network.');
		}
		$network_domain = $network->domain;
		$network_path   = (string) $network->path;
		$network_id     = (int) $network->id;
		$main_site_id   = (int) get_main_site_id();

		$this->blogs = array();
		foreach (get_sites(array('number' => 500, 'network_id' => $network_id)) as $site) {
			$blog_id = (int) $site->blog_id;
			if ($blog_id === $main_site_id) {
				$this->blogs['network'] = $blog_id;
				continue;
			}
			$host = strtolower((string) wp_parse_url(get_home_url($blog_id), PHP_URL_HOST));
			$slug = isset(explode('.', $host)[0]) ? explode('.', $host)[0] : '';
			if (in_array($slug, self::FARM_ROLES, true)) {
				$this->blogs[$slug] = $blog_id;
			}
		}

		$created_any = false;
		$owner_id = $this->default_owner_id();

		foreach (self::FARM_ROLES as $slug) {
			if (isset($this->blogs[$slug])) {
				continue;
			}
			$this->log('Creating sub-site: ' . $slug);
			$blog_id = wpmu_create_blog(
				$slug . '.' . $network_domain,
				$network_path,
				'Le Paysan Urbain ' . ucfirst($slug),
				$owner_id,
				// wpmu_create_blog() defaults a new site to public => 0, but
				// the sites are public
				array('public' => 1),
				$network_id
			);
			if (is_wp_error($blog_id)) {
				$this->fail('Could not create sub-site ' . $slug . ': ' . $blog_id->get_error_message());
			}
			clean_blog_cache((int) $blog_id);
			$this->blogs[$slug] = (int) $blog_id;
			$created_any          = true;
		}

		// wp-env installs the network before applying SUBDOMAIN_INSTALL. Keep
		// the network metadata aligned with the subdomain choice.
		update_site_option('subdomain_install', 1);

		// Name the network site (a fresh install defaults to "WordPress").
		$this->with_blog(
			$main_site_id,
			function () {
				if ('Le Paysan Urbain' !== (string) get_option('blogname')) {
					update_option('blogname', 'Le Paysan Urbain');
				}
			}
		);

		if ($created_any) {
			$this->log('Created one or multiple missing sub-site.');
		}
		$this->log('Network sites: ' . implode(', ', array_keys($this->blogs)));
		return $this->blogs;
	}

	/**
	 * Build the placeholder => URL map for content fragments, so the files can
	 * be written once with tokens ({{NETWORK_URL}}, {{FARM_PARIS_URL}}, ...)
	 * and resolved at runtime to the real site URLs.
	 *
	 * @return array<string, string>
	 */
	protected function resolve_urls()
	{
		$blogs = $this->ensure_blogs();
		$map   = array();

		foreach (array('network', 'paris', 'lyon', 'marseille') as $role) {
			if (! isset($blogs[$role])) {
				continue;
			}
			$url = get_home_url($blogs[$role]);
			if ('network' === $role) {
				$map['{{NETWORK_URL}}'] = trailingslashit($url);
			} else {
				$map['{{FARM_' . strtoupper($role) . '_URL}}'] = trailingslashit($url);
			}
		}

		return $map;
	}

	/**
	 * Replace the URL placeholder tokens in a content fragment.
	 *
	 * @param string $content Content.
	 * @return string
	 */
	protected function apply_urls($content)
	{
		$map = $this->resolve_urls();
		return str_replace(array_keys($map), array_values($map), $content);
	}

	/**
	 * Find the ID of a post by slug within the current site.
	 * The caller is responsible for having switched to the target site.
	 *
	 * @param string $post_type Post type.
	 * @param string $name      Post slug (post_name).
	 * @return int 0 when none.
	 */
	protected function find_post_by_name($post_type, $name)
	{
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
	 * Find a post by its slug, also matching the `__trashed` variant that
	 * WordPress gives a trashed post's slug (it renames the slug on trash, so
	 * the original name no longer matches). Returning the trashed post lets the
	 * caller restore it instead of creating a duplicate.
	 *
	 * @param string $post_type Post type slug.
	 * @param string $name      Clean post slug.
	 * @return int Post ID, 0 when none.
	 */
	protected function find_post_by_name_or_trashed($post_type, $name)
	{
		$by_name = $this->find_post_by_name($post_type, $name);
		if ($by_name) {
			return $by_name;
		}

		$posts = get_posts(
			array(
				'post_type'      => $post_type,
				'post_status'    => array('publish', 'draft', 'pending', 'private', 'future', 'trash'),
				'name'           => $name . '__trashed',
				'posts_per_page' => 1,
				'fields'         => 'ids',
			)
		);
		return $posts ? (int) $posts[0] : 0;
	}

	/**
	 * Verify the theme and the project-local plugins required by the content are
	 * present before provisioning starts.
	 *
	 * The provisioning content depends on the `lepaysanurbain` theme and the
	 * project-local blocks and patterns. This runs after the environment steps
	 * (and after `init`), so a missing dependency shows up as a clear error here
	 * instead of an obscure failure mid-run.
	 *
	 * The local plugins must be *network-active* — this is what makes their
	 * blocks and patterns available on every farm site, not just in the current
	 * request. A plugin active only on the main site would appear to work during
	 * the run but leave the farm sites without the blocks/patterns they need, so
	 * we require `is_plugin_active_for_network()` rather than checking the
	 * in-request registry (which is unreliable on a first run where a plugin is
	 * network-activated mid-request, before its code is loaded).
	 *
	 * @return void
	 */
	protected function check_dependencies()
	{
		if (! function_exists('is_plugin_active_for_network')) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$theme = wp_get_theme(self::THEME_SLUG);
		if (! $theme->exists()) {
			$this->fail('Required theme missing: ' . self::THEME_SLUG);
		}

		foreach ($this->local_plugins() as $plugin) {
			if (! is_plugin_active_for_network($plugin)) {
				$this->fail('Required local plugin is not network-active: ' . $plugin);
			}
		}

		$this->log('Dependencies OK: theme, plugins');
	}

	/**
	 * Resolve the first network super admin that exists as a user, or null.
	 *
	 * @return WP_User|null
	 */
	protected function first_super_admin_user()
	{
		if (! function_exists('get_super_admins')) {
			return null;
		}
		foreach (get_super_admins() as $login) {
			$user = get_user_by('login', $login);
			if ($user) {
				return $user;
			}
		}
		return null;
	}

	/**
	 * Resolve the user that should own newly created sub-sites and fixtures.
	 *
	 * The old code assumed user ID 1. On an existing shared-host multisite the
	 * provisioning may be run by a different (super) admin, so prefer the first
	 * network super admin, then the current user, and only fall back to user 1
	 * as a last resort.
	 *
	 * @return int User ID.
	 */
	protected function default_owner_id()
	{
		$super_admin = $this->first_super_admin_user();
		if ($super_admin) {
			return (int) $super_admin->ID;
		}
		$current = wp_get_current_user();
		if ($current instanceof WP_User && $current->ID) {
			return (int) $current->ID;
		}
		return 1;
	}

	/**
	 * Install the fr_FR core language pack, if it is not already installed.
	 *
	 * This is the PHP equivalent of `wp language core install fr_FR`: it
	 * downloads the pack from wordpress.org and writes it into WP_LANG_DIR
	 * (shared across all sites on a multisite, so one install is enough). It
	 * works both from WP-CLI and from the wp-admin button (no CLI needed).
	 *
	 * The download can be blocked on some hosts (no outbound HTTP to
	 * wordpress.org, or no write access to the language directory). We return
	 * false in that case and let the caller stop the run with a clear error so
	 * the operator is not shown a false "terminé sans erreur" (the site would
	 * otherwise stay in English).
	 *
	 * @param string $locale Language code to install (default fr_FR).
	 * @return bool True when the pack is available (installed or already
	 *              present), false when it could not be installed.
	 */
	protected function install_language_pack($locale = 'fr_FR')
	{
		if (! function_exists('wp_download_language_pack')) {
			require_once ABSPATH . 'wp-admin/includes/translation-install.php';
		}

		$installed = wp_download_language_pack($locale);
		if ($locale === $installed) {
			$this->log('language pack ' . $locale . ' installed (or already present)');
			return true;
		}

		return false;
	}

	/**
	 * Register the lepaysanurbain theme's patterns in the block-pattern registry.
	 *
	 * Core only registers a theme's patterns when that theme is the active one
	 * at `init`. During a single provisioning request the theme might be
	 * switched to only mid-run, so we register its patterns ourselves using the
	 * same core mechanism (WP_Theme::get_block_patterns scans the theme's own
	 * patterns/ directory regardless of which theme is active).
	 *
	 * @return void
	 */
	protected function register_theme_patterns()
	{
		$theme = wp_get_theme(self::THEME_SLUG);
		if (! $theme->exists()) {
			$this->fail('Theme not found: ' . self::THEME_SLUG);
		}
		$registry    = WP_Block_Patterns_Registry::get_instance();
		$dirpath     = trailingslashit($theme->get_stylesheet_directory()) . 'patterns/';
		$text_domain = $theme->get('TextDomain');

		foreach ($theme->get_block_patterns() as $file => $pattern) {
			if ($registry->is_registered($pattern['slug'])) {
				continue;
			}
			$pattern['filePath'] = $dirpath . $file;
			// phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText,WordPress.WP.I18n.NonSingularStringLiteralDomain,WordPress.WP.I18n.LowLevelTranslationFunction
			$pattern['title'] = translate_with_gettext_context($pattern['title'], 'Pattern title', $text_domain);
			if (! empty($pattern['description'])) {
				// phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText,WordPress.WP.I18n.NonSingularStringLiteralDomain,WordPress.WP.I18n.LowLevelTranslationFunction
				$pattern['description'] = translate_with_gettext_context($pattern['description'], 'Pattern description', $text_domain);
			}
			register_block_pattern($pattern['slug'], $pattern);
		}
	}

	/**
	 * Import a file into the current site's media library, or reuse an existing
	 * attachment with the same filename.
	 *
	 * @param string $file_path Absolute filesystem path.
	 * @param string $title     Attachment title.
	 * @return int
	 */
	protected function import_attachment($file_path, $title)
	{
		if (! file_exists($file_path)) {
			$this->fail('Missing asset: ' . $file_path);
		}

		$filename = basename($file_path);

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
		if ($existing) {
			return (int) $existing[0];
		}

		// SVG is not in core's allowed mime list; the theme normally allows it
		// via an upload_mimes filter, but its functions.php is not loaded in
		// this single request. Allow SVG ourselves so wp_upload_bits passes.
		$ext        = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
		$mime_filter = function ($mimes) {
			$mimes['svg'] = 'image/svg+xml';
			return $mimes;
		};
		add_filter('upload_mimes', $mime_filter);

		$uploaded = wp_upload_bits($filename, null, (string) file_get_contents($file_path));

		remove_filter('upload_mimes', $mime_filter);

		if (! empty($uploaded['error'])) {
			$this->fail('Could not write ' . $filename . ': ' . $uploaded['error']);
		}

		if ('svg' === $ext) {
			$filetype = array(
				'ext'  => 'svg',
				'type' => 'image/svg+xml',
			);
		} else {
			$filetype = wp_check_filetype_and_ext($uploaded['file'], $filename);
			if (empty($filetype['type'])) {
				$this->fail('Unsupported upload type for ' . $filename);
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
		if (is_wp_error($attach_id)) {
			$this->fail('Could not attach ' . $filename . ': ' . $attach_id->get_error_message());
		}

		$metadata = wp_generate_attachment_metadata($attach_id, $uploaded['file']);
		wp_update_attachment_metadata($attach_id, $metadata);

		return (int) $attach_id;
	}

	/**
	 * Tag a navigation or template-part post with its theme/area terms and the
	 * origin meta, then flush the caches WordPress keeps for these.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $area    Template part area ('' for a navigation).
	 * @return void
	 */
	protected function tag_template_element($post_id, $area)
	{
		wp_set_post_terms($post_id, self::THEME_SLUG, 'wp_theme', false);
		if ('' !== $area) {
			wp_set_post_terms($post_id, $area, 'wp_template_part_area', false);
		}
		update_post_meta($post_id, 'origin', 'theme');
		clean_post_cache($post_id);
	}

	/**
	 * Create or update a wp_navigation post from the given block content.
	 *
	 * Header menus are refreshed on every run so they always match the current
	 * fragment file. Footers pass $update_existing=false so an existing footer
	 * menu is created once and left alone, letting editorial edits persist.
	 *
	 * @param string $title           Navigation title.
	 * @param string $navigation_name Post slug.
	 * @param string $content         Navigation block content.
	 * @param bool   $update_existing Whether to refresh an existing post.
	 * @return int Navigation post ID.
	 */
	protected function create_or_update_navigation($title, $navigation_name, $content, $update_existing = true)
	{
		$nav_id = $this->find_post_by_name('wp_navigation', $navigation_name);
		if ($nav_id) {
			if ($update_existing) {
				wp_update_post(
					array(
						'ID'           => $nav_id,
						'post_title'   => $title,
						'post_content' => $content,
					)
				);
				$this->log('Updated navigation ' . $navigation_name . ' (' . $nav_id . ')');
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
			if (is_wp_error($nav_id)) {
				$this->fail($nav_id->get_error_message());
			}
			$this->log('Created navigation ' . $navigation_name . ' (' . $nav_id . ')');
		}
		$this->tag_template_element((int) $nav_id, '');
		return (int) $nav_id;
	}

	/**
	 * Create or update the site-local template-part overlay for a part name
	 * (header/footer), injecting the navigation ref into its content.
	 *
	 * @param string $part_name     Template part post slug (header/footer).
	 * @param string $part_title    Human title.
	 * @param int    $navigation_id Navigation ID to reference.
	 * @param string $template_file Full path to the theme template part file.
	 * @return int Template part post ID.
	 */
	protected function create_or_update_template_part($part_name, $part_title, $navigation_id, $template_file)
	{
		if (! file_exists($template_file)) {
			$this->fail('Missing theme template part file: ' . $template_file);
		}
		$template_content = (string) file_get_contents($template_file);
		$template_content = preg_replace(
			'/(<!-- wp:navigation \{)/',
			'$1"ref":' . (int) $navigation_id . ',',
			$template_content,
			1
		);

		$part_id = $this->find_post_by_name('wp_template_part', $part_name);
		if ($part_id) {
			wp_update_post(
				array(
					'ID'           => $part_id,
					'post_content' => $template_content,
				)
			);
			$this->log('Updated template part ' . $part_name . ' (' . $part_id . ')');
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
			if (is_wp_error($part_id)) {
				$this->fail($part_id->get_error_message());
			}
			$this->log('Created template part ' . $part_name . ' (' . $part_id . ')');
		}

		$area = ('header' === $part_name) ? 'header' : 'footer';
		$this->tag_template_element((int) $part_id, $area);

		return (int) $part_id;
	}

	/**
	 * Create a page on the current site, keeping it untouched if it exists.
	 *
	 * @param array<string, string> $fields Post fields (must include post_name).
	 * @return int
	 */
	protected function upsert_page($fields)
	{
		$page_id = $this->find_post_by_name('page', $fields['post_name']);
		if ($page_id) {
			// Like the original scripts, an existing page is left untouched.
			return (int) $page_id;
		}

		$fields = array_merge(
			array(
				'post_type'     => 'page',
				'post_status'   => 'publish',
				'post_author'   => $this->default_owner_id(),
				'comment_status' => 'closed',
				'ping_status'   => 'closed',
			),
			$fields
		);
		$page_id = wp_insert_post($fields, true);
		if (is_wp_error($page_id)) {
			$this->fail($page_id->get_error_message());
		}
		return (int) $page_id;
	}

	/**
	 * Build the patterns review page content from the block-pattern registry.
	 *
	 * @return string
	 */
	protected function assemble_patterns_page()
	{
		$split_section_namespace = 'lpu-split-section/';
		$theme_namespace         = trailingslashit(get_stylesheet());
		$patterns                = array();

		foreach (WP_Block_Patterns_Registry::get_instance()->get_all_registered() as $pattern) {
			if (! isset($pattern['name'], $pattern['content'])) {
				continue;
			}
			$source  = isset($pattern['source']) ? (string) $pattern['source'] : '';
			$is_theme = 'theme' === $source || 0 === strpos((string) $pattern['name'], $theme_namespace);
			$is_split = 0 === strpos((string) $pattern['name'], $split_section_namespace);
			if ($is_theme || $is_split) {
				$patterns[$pattern['name']] = $pattern;
			}
		}

		ksort($patterns, SORT_NATURAL | SORT_FLAG_CASE);
		if (! $patterns) {
			$this->fail('No patterns provided by the active theme or project-local plugins.');
		}

		$content = '';
		foreach ($patterns as $pattern) {
			$content .= $this->pattern_with_metadata($pattern['content'], $pattern) . "\n";
		}
		return $content;
	}

	/**
	 * Stamp pattern metadata onto the first block of a pattern's content, like
	 * the editor does when a pattern is inserted.
	 *
	 * @param string $content Pattern content.
	 * @param array  $pattern Pattern definition.
	 * @return string
	 */
	protected function pattern_with_metadata($content, $pattern)
	{
		$blocks = parse_blocks($content);
		if (! isset($blocks[0]['blockName']) || '' === $blocks[0]['blockName']) {
			$this->fail('Pattern content does not start with a block: ' . ($pattern['name'] ?? 'unknown'));
		}

		$blocks[0]['attrs']['metadata'] = array(
			'categories'  => array_values($pattern['categories'] ?? array()),
			'patternName' => $pattern['name'],
			'name'        => $pattern['title'],
		);

		return serialize_blocks($blocks);
	}

	/**
	 * Read the ordered list of pattern names for the network Home.
	 *
	 * @return array<int, string>
	 */
	protected function read_pattern_order()
	{
		$order         = $this->read_content('home-network/home-sections-names.txt');
		$pattern_order = array();
		foreach (preg_split('/\r?\n/', $order) as $line) {
			$line = trim($line);
			if ('' === $line || '#' === $line[0]) {
				continue;
			}
			if (false !== strpos($line, "'")) {
				$this->fail('Pattern name contains an unsupported quote: ' . $line);
			}
			$pattern_order[] = $line;
		}
		if (! $pattern_order) {
			$this->fail('No patterns declared in home-sections-names.txt.');
		}
		return $pattern_order;
	}

	/**
	 * Build the farm-label => home-URL map (Paris, Lyon, Marseille), failing if
	 * any farm URL cannot be resolved.
	 *
	 * @return array<string, string>
	 */
	protected function farm_site_urls()
	{
		$farm_labels = array(
			'paris'     => 'Paris',
			'lyon'      => 'Lyon',
			'marseille' => 'Marseille',
		);
		$farm_urls   = array();
		foreach (get_sites(array('number' => 100, 'network_id' => get_current_network_id())) as $site) {
			$site_home  = trailingslashit(get_home_url((int) $site->blog_id));
			$host       = strtolower((string) wp_parse_url($site_home, PHP_URL_HOST));
			$host_parts = explode('.', $host);
			$site_key   = $host_parts[0] ?? '';
			if (isset($farm_labels[$site_key])) {
				$farm_urls[$farm_labels[$site_key]] = $site_home;
			}
		}
		foreach ($farm_labels as $label) {
			if (! isset($farm_urls[$label])) {
				$this->fail('Could not resolve the multisite URL for farm: ' . $label);
			}
		}
		return $farm_urls;
	}

	/**
	 * Find and validate the network Home page, then return its ID.
	 *
	 * Fails unless the page exists, has the expected title, is the current
	 * static front page, so the provisioning writes to the intended page.
	 *
	 * @param string $page_title Expected page title.
	 * @param string $page_slug  Expected page slug.
	 * @return int
	 */
	protected function find_validated_home_page($page_title, $page_slug)
	{
		$pages = get_posts(
			array(
				'post_type'      => 'page',
				'post_status'    => array('publish', 'draft', 'pending', 'private', 'future', 'trash'),
				'name'           => $page_slug,
				'posts_per_page' => 2,
				'orderby'        => 'ID',
				'order'          => 'ASC',
				'fields'         => 'ids',
			)
		);
		if (count($pages) > 1) {
			$this->fail('More than one page uses the Home slug: ' . $page_slug);
		}
		if (! $pages) {
			$this->fail('The technical front page does not exist (' . $page_slug . '). Run front pages first.');
		}

		$page_id = (int) $pages[0];
		$page    = get_post($page_id);
		if ('trash' === $page->post_status && ! wp_untrash_post($page_id)) {
			$this->fail('Could not restore the network Home page: ' . $page_id);
		}
		if ((string) $page->post_title !== $page_title) {
			$this->fail('The page title is not the expected Home title: ' . $page->post_title);
		}
		if ('page' !== get_option('show_on_front') || (int) get_option('page_on_front') !== $page_id) {
			$this->fail('The expected Home page is not the current page_on_front. Run front pages first.');
		}

		return $page_id;
	}

	/**
	 * Assemble the network Home page content from the ordered patterns,
	 * applying the French copy and the real farm URLs.
	 *
	 * @param array<int, string>   $pattern_order Ordered pattern names.
	 * @param array<string, string> $farm_urls     Paris/Lyon/Marseille home URLs.
	 * @return string
	 */
	protected function assemble_home_content($pattern_order, $farm_urls)
	{
		$patterns_by_name = array();
		foreach (WP_Block_Patterns_Registry::get_instance()->get_all_registered() as $pattern) {
			if (isset($pattern['name'], $pattern['content'])) {
				$patterns_by_name[$pattern['name']] = $pattern;
			}
		}

		$farm_labels = array(
			'paris'     => 'Paris',
			'lyon'      => 'Lyon',
			'marseille' => 'Marseille',
		);

		$page_content      = '';
		$cards_occurrences = 0;
		foreach ($pattern_order as $pattern_name) {
			if (! isset($patterns_by_name[$pattern_name])) {
				$this->fail('Active theme pattern is missing: ' . $pattern_name);
			}
			$pattern = $patterns_by_name[$pattern_name];
			$section = $pattern['content'];

			if ('lepaysanurbain/hero' === $pattern_name) {
				$section = $this->home_text($section, 'Titre principal de la page', 'Cultiver le vivant en ville.', 'Home hero title');
				$section = $this->home_text($section, 'Présentez ici le sujet principal de la page en quelques mots.', 'Présentez ici la promesse de cette page et le rôle du Paysan Urbain dans la ville.', 'Home hero text');
			}

			if ('lpu-split-section/split-content-image' === $pattern_name) {
				$section = $this->home_text($section, 'Sur-titre', 'Une histoire à raconter', 'Home split content eyebrow');
				$section = $this->home_text($section, 'Un titre qui tient dans sa moitié', 'Présentez votre action sur deux lignes', 'Home split content title');
				$section = $this->home_text($section, 'Ajoutez ici le texte, les informations et les appels à l’action propres à cette zone.', 'Ajoutez ici quelques lignes pour expliquer le projet, son utilité et la manière dont le visiteur peut y prendre part.', 'Home split content text');
			}

			if ('lepaysanurbain/network-farm-selector' === $pattern_name) {
				$farm_placeholders = array(
					'Paris'     => 'Ferme 1',
					'Lyon'      => 'Ferme 2',
					'Marseille' => 'Ferme 3',
				);
				foreach ($farm_labels as $label) {
					$needle      = '<a>' . $farm_placeholders[$label] . '</a>';
					$replacement = '<a href="' . esc_url($farm_urls[$label]) . '">' . $label . '</a>';
					$section     = str_replace($needle, $replacement, $section, $link_count);
					if (1 !== $link_count) {
						$this->fail('Expected one unconfigured farm link for ' . $label);
					}
				}
			}

			if ('lepaysanurbain/cards' === $pattern_name) {
				$cards_occurrences++;
				$section = $this->home_text($section, 'Titre de la grille', 1 === $cards_occurrences ? 'Des façons d’agir' : 'Le réseau en action', 'Home cards title ' . $cards_occurrences);

				$card_titles = 1 === $cards_occurrences
					? array('Particuliers', 'Professionnels', 'Partenaires et institutions')
					: array('Activités et événements', 'Production locale', 'Projets et insertion');
				$generic_card_titles = array('Titre de carte 1', 'Titre de carte 2', 'Titre de carte 3');
				foreach ($generic_card_titles as $index => $generic_title) {
					$section = $this->home_text($section, $generic_title, $card_titles[$index], 'Home card title ' . ($index + 1));
				}

				if (2 === $cards_occurrences) {
					$section = str_replace(
						'"backgroundColor":"ecru","className":"lpu-card-grid lpu-motif lpu-motif-1-bandeau"',
						'"backgroundColor":"vert-grise","className":"lpu-card-grid lpu-card-grid--titles-only"',
						$section,
						$outer_attribute_count
					);
					if (1 !== $outer_attribute_count) {
						$this->fail('Expected the second cards pattern wrapper attributes.');
					}
					$section = str_replace(
						'lpu-card-grid lpu-motif lpu-motif-1-bandeau has-ecru-background-color has-background',
						'lpu-card-grid lpu-card-grid--titles-only has-vert-grise-background-color has-background',
						$section,
						$outer_class_count
					);
					if (1 !== $outer_class_count) {
						$this->fail('Expected the second cards pattern wrapper classes.');
					}
					$section = preg_replace('/\s*<!-- wp:paragraph\b.*?<!-- \/wp:paragraph -->/s', '', $section, -1, $paragraph_count);
					if (null === $section || 3 !== $paragraph_count) {
						$this->fail('Expected three optional card descriptions in the second cards pattern.');
					}
					$section = preg_replace('/\s*<!-- wp:buttons\b.*?<!-- \/wp:buttons -->/s', '', $section, -1, $button_count);
					if (null === $section || 3 !== $button_count) {
						$this->fail('Expected three optional card buttons in the second cards pattern.');
					}
				} else {
					$section = str_replace('>En savoir plus<', '>Découvrir<', $section, $button_label_count);
					if (3 !== $button_label_count) {
						$this->fail('Expected three generic card button labels in the first cards pattern.');
					}
					$card_descriptions = array(
						'Décrivez brièvement le contenu de cette carte et son intérêt pour vos visiteurs.' => 'Visiter, participer, découvrir.',
						'Ajoutez une information courte sur cette proposition.' => 'Commander des produits locaux.',
						'Présentez un troisième contenu ou une action à découvrir.' => 'Soutenir, collaborer, développer des projets.',
					);
					foreach ($card_descriptions as $generic_text => $home_text) {
						$section = $this->home_text($section, $generic_text, $home_text, 'Home card description');
					}
				}
			}

			if ('lepaysanurbain/columns' === $pattern_name) {
				$section = $this->home_text($section, 'Titre commun', 'Un message commun à faire vivre', 'Home columns title');
				$section = $this->home_text($section, 'Premier message à présenter dans cette colonne.', 'Présentez ici un premier message court, une information ou une valeur importante du projet.', 'Home columns text 1');
				$section = $this->home_text($section, 'Deuxième message à présenter dans cette colonne.', 'Utilisez cette colonne pour compléter le propos avec un deuxième message lisible et autonome.', 'Home columns text 2');
				$section = $this->home_text($section, 'Troisième message à présenter dans cette colonne.', 'Ajoutez un dernier repère, un chiffre ou un lien vers une information complémentaire.', 'Home columns text 3');
			}

			if ('lpu-split-section/split-motif-image' === $pattern_name) {
				$section = $this->home_text($section, 'Sur-titre', 'Une ferme, des savoir-faire', 'Home motif eyebrow');
				$section = $this->home_text($section, 'Titre de la mise en avant', 'Cultiver et transmettre au quotidien', 'Home motif title');
				$section = $this->home_text($section, 'Présentez ici le contenu de cette mise en avant.', 'Décrivez ici l’action mise en avant, les personnes concernées et la manière dont cette initiative fait grandir le vivant en ville.', 'Home motif text');
			}

			if ('lepaysanurbain/graphic-band' === $pattern_name) {
				$section = $this->home_text($section, 'Titre de l’appel à l’action', 'Prêt à cultiver le vivant avec nous&nbsp;?', 'Home graphic title');
				$section = $this->home_text($section, 'Ajoutez ici une phrase courte pour guider vos visiteurs.', 'Rassemblez ici les dernières informations utiles et invitez vos visiteurs à passer à l’action.', 'Home graphic text');
			}

			$page_content .= $this->pattern_with_metadata($section, $pattern) . "\n";
		}

		if (2 !== $cards_occurrences) {
			$this->fail('The Home order must contain exactly two cards patterns.');
		}

		return $page_content;
	}

	/**
	 * Replace a single text slot in a content fragment, asserting exactly one
	 * occurrence, and return the edited fragment.
	 *
	 * @param string $content Content.
	 * @param string $from    Text to replace.
	 * @param string $to      Replacement text.
	 * @param string $label   Slot label used in the assertion error.
	 * @return string
	 */
	protected function home_text($content, $from, $to, $label)
	{
		$count  = 0;
		$result = str_replace('>' . $from . '<', '>' . $to . '<', $content, $count);
		if (1 !== $count) {
			$this->fail('Expected one Home content slot for ' . $label . ', found ' . $count);
		}
		return $result;
	}
}
