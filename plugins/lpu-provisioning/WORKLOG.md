# LPU Provisioning — work log

High-level record of what is being done. Entries are dated and timestamped
(YYYY-MM-DD HH:MM).

## 2026-09-02

- Ported the bash `wp-env` provisioning scripts into a single PHP plugin
  `lpu-provisioning` (in worktree `site/lpu-provisioning`, port 8889).
- Two review subagents reviewed the code; applied their MUST-FIX findings:
  nav `ref` injection (double-brace), TSV delimiter default, SVG import
  self-sufficiency, `switch_theme`, network blogname, create-only test
  page/footer nav, eager WP-CLI registration.
- Wrote plugin README, bootstrap, CLI command, admin (network page + button).
- Started the fresh worktree environment (port 8889).
- Test run of `wp lpu provision` revealed and fixed:
  - `clean_site_cache()` not available in WP 7.1 -> use `clean_blog_cache()`.
  - SVG upload rejected by `wp_upload_bits` because the theme's `upload_mimes`
    filter isn't loaded in the single CLI request.
- SVG upload now works: added an `upload_mimes` filter (svg) scoped around
  `wp_upload_bits` in the plugin itself, so it no longer depends on the theme's
  functions.php being loaded in the single request.
- `wp lpu provision` now runs end-to-end successfully (sub-sites, theme,
  language, logos, front pages, header navs, footers, test page, patterns test
  page, network Home).
- Verified CLI path:
  - image input not supported by this model -> visual checks via DOM/curl instead.
  - nav ref injection valid (`{"ref":7,"overlayMenu":...}`), URL placeholders
    substituted (`http://paris.lepaysanurbain.test:8889/` etc).
  - network home renders (HTTP 200, no console/page errors, no failed
    requests), farm selector links point to the 3 farms, footer present.
  - farm pages (paris) render with title + header + footer + nav + logo.
  - idempotency: 2nd run updates navs/parts/patterns, refuses to overwrite
    network Home without --force (matches design).
- Review subagent on the wp-admin path found a CRITICAL bug before testing:
  the admin_post handler was registered inside the `network_admin_menu` hook,
  which never fires during an admin-post.php request -> the button would 400.
  Fixed: register the handler on `admin_init` (fires for admin-post), menu on
  `network_admin_menu` separately.
- Hardened the admin handler: `set_time_limit(0)` + `wp_raise_memory_limit`,
  and `catch ( Throwable $e )` so PHP errors are stored/reported instead of a
  blank fatal page.
- Clarified the network-Home protection in the admin copy (re-run without
  --force is refused once the Home contains assembled content; that's the
  intended anti-clobber guard).

## 2026-09-03

- NEXT: run a CLEAN admin-only end-to-end test: reset wp-env DB (no CLI run),
  trigger the admin-post handler, assert the network settings page shows a
  success notice + full log with no error.
- Resetting/recreating the environment via npm run cleanup + start.
- Because wordpress-inspector is read-only (won't click the provisioning
  button or enter credentials), testing the admin path with an authenticated
  curl session (admin/password, cookies) instead: login, fetch network
  settings page, extract nonce, POST to admin-post.php, then GET the page and
  check the stored result.

## 2026-09-03 04:19

- Admin-path (HTTP) test via authenticated curl (admin/password, cookie jar):
  - login OK, network LPU settings page renders form + nonce + submit button.
  - POST to admin-post.php works (no 400 -> registration fix confirmed), result
    stored and shown back on the page after redirect.
  - HTTP path created sub-sites + theme + language + logos + front pages +
    navs + footers + test page + patterns page, then FAILED at last step:
    "Active theme pattern is missing: lepaysanurbain/hero".
- Root cause: theme patterns (themes/lepaysanurbain/patterns/*.php) are
  registered by core only when the theme is the ACTIVE theme at `init`. In a
  single HTTP request where the theme wasn't active at bootstrap, patterns are
  absent -> home assembly fails. CLI only "worked" because a prior run had
  already activated the theme at bootstrap.
  - => NEEDS FIX for the OVH clean-DB button path.
- USER REQUEST (04:19): the provisioner code is over-engineered; refactor toward
  simple, easy-to-read code "a bit like the .sh approach". Also fix the
  pattern-registration bug.
  - NEXT: read the full class, plan a readable refactor + the pattern fix
    (consult a review subagent on the design), then implement and retest both
    the CLI and the wp-admin button paths end-to-end.

## 2026-09-03 04:37

- REFACTOR (validate-then-implement; design approved by subagent): split the
  monolithic provisioner into:
  - `inc/class-lpu-util.php` — new trait `Lpu_Util` holding ALL the machinery
    (read_content/read_tsv, with_blog, ensure_blogs, resolve/apply_urls,
    import_attachment, create_or_update_navigation/template_part, upsert_page,
    assemble_patterns_page, pattern_with_metadata, home_text, read_pattern_order,
    farm_site_urls, find_validated_home_page, assemble_home_content) plus NEW
    helpers `register_theme_patterns()`, `each_site_row()`. `Lpu_Provision_Error`
    moved here too.
  - `inc/class-lpu-provisioner.php` — rewritten so it only declares the readable
    `provision_*` steps + `provision()` + `steps()`, using the trait. Role-keyed
    steps (logos/front pages/navs/footers) now iterate via `each_site_row()`;
    network-only steps (test page / patterns page / Home) keep explicit
    `with_blog('network')`.
  - Zero call-site changes: `Lpu_Provisioner::get_log()`, `new Lpu_Provisioner()`,
    `catch (Throwable)` all keep working. All 5 plugin PHP files lint clean.
- PATTERN-BUG FIX: added `register_theme_patterns()` (trait) called once at the
  top of `provision()`: scans `wp_get_theme('lepaysanurbain')->get_block_patterns()`
  and `register_block_pattern()` each (guarded by `is_registered()`), mirroring
  core `_register_theme_block_patterns()` (filePath + gettext translation of
  title/description). Safe for a non-active theme (WP_Theme reads its own
  stylesheet), safe after `init`, idempotent. This is what makes the OVH clean-DB
  HTTP button path able to assemble the Home in a single request.
- Home assembly preserved byte-for-byte semantics from the original (page.tsv
  title/slug/post_status, lpu_header_transparent meta, order/count assertions,
  farm-link URL substitution, text-slot replacements) — decomposed into
  `read_pattern_order()`, `farm_site_urls()`, `find_validated_home_page()`,
  `assemble_home_content()` for readability.
  - NEXT: lint done; commit the refactor, then re-test BOTH paths end-to-end:
    (a) clean CLI run, (b) clean admin-only HTTP run (should now assemble the
    network Home and patterns page without the pattern error).

## 2026-09-03 04:45

- Refactor committed (7e2ace9). Verified the pattern fix closes the clean-DB gap
  on BOTH paths.
- CLI path on a clean DB (reset env, only blog 1 + network, no sub-sites):
  `wp lpu provision` runs end-to-end to "Provisioning complete" — sub-sites,
  theme, language, logos, front pages, navs, footers, test page, patterns test
  page, network Home. Network Home (200) renders hero text "Cultiver le vivant
  en ville"; patterns page (200) + post content has 9 patternName stamps.
- Admin HTTP path on a fresh clean DB (authenticated curl: login → extract
  nonce from the network settings page → POST admin-post.php):
  - POST returns 302 (no 400 -> admin_init handler works).
  - Result page shows success notice "Provisionnement terminé sans erreur", no
    error notice, and the full log ends with "Provisioning complete".
  - NO "Active theme pattern is missing: lepaysanurbain/hero" anymore ->
    register_theme_patterns() fixes the single-request path (the OVH clean-DB
    button case).
  - Network Home post content has all 8 expected patterns with patternName
    metadata (hero, 2x cards, columns, graphic-band, network-farm-selector,
    split-content-image, split-motif-image); Home + patterns page + paris
    sub-site all return HTTP 200.
- Both the CLI and the wp-admin button paths are now verified end-to-end from a
  clean multisite.

## 2026-09-03 08:35

- Re-verified per-site rendering on ALL four sites (posts a review note that Lyon
  and Marseille had not been individually render-checked). A user `wp-env
  cleanup` had been run; restarted, then confirmed the 4 sites (network 1,
  paris 2, lyon 3, marseille 4) were still fully provisioned from the earlier
  verified admin-path run (theme=lepaysanurbain, blogname per city,
  show_on_front=page, page_on_front=5, navs menu-principal/footer-principal,
  template parts header/footer, custom_logo=3), so no fresh provisioning was
  needed.
  - Network home HTTP 200 + hero "Cultiver le vivant en ville"; patterns page
    HTTP 200.
  - paris / lyon / marseille each HTTP 200 with per-city title, header nav
    anchor links (local nav: #qui-sommes-nous ...), footer, custom logo,
    2 wp-block-template-part blocks.
- Fixed README accuracy after the refactor:
  - added `inc/class-lpu-util.php` (trait `Lpu_Util`, the machinery) to
    "Fichiers" and clarified `Lpu_Provisioner` are the readable steps.
  - corrected the "init tardif pour la commande CLI" line -> the bootstrap
    registers the WP-CLI command immediately when WP_CLI is defined.
- updated "Maintenabilité" so the live-WP-CLI/log collection description now
  refers to the trait, not the provisioner class.

## 2026-09-03 10:14

- Addressed the code-review findings on the ported plugin:
  1. Sub-sites created via `wpmu_create_blog()` now pass `array('public' => 1)`
     (defaults to 0 -> farm sites were not public). Verified: blog_public=1 on
     all three farms.
  2. Patterns test page: lookup now also matches the `__trashed` slug that
     WordPress gives a trashed page (the shell had this latent bug too — a
     trashed page was never found and a duplicate was created). Added trait
     helper `find_post_by_name_or_trashed()`; update path restores status to
     `publish` (TSV value) after untrash. Verified: trash -> provision -> page
     untrashed, slug restored, publish, no duplicate.
  3. Home `lpu_header_transparent` meta now set via `update_post_meta()` (repair
     path) — verified value 1.
  4. New `check_dependencies()` (trait, called at top of `provision()`): fails
     loudly if theme `lepaysanurbain`, block `lpu/nav-group` or patterns
     `lpu-split-section/*` are missing. README OVH steps now require
     network-activating those two plugins. Verified message on run.
  5. `create_or_update_template_part()` fails loudly if the theme template-part
     file is missing (added `file_exists()` guard).
- All five fixes verified end-to-end from a clean DB via the CLI path
  (`wp lpu provision --force` -> "Provisioning complete").
- Environment note: after a `cleanup`, `wp-env start` failed because GitHub was
  rate-limiting unauthenticated clones of the WordPress sources. Works around:
  the WordPress and the WordPress-PHPUnit (tests) repos under wp-env's workdir
  are now shallow git clones with an SSH origin
  (git@github.com:WordPress/...), so wp-env reuses them instead of cloning over
  HTTPS.

## 2026-09-03 10:43

- Review subagent verified all five fixes against WP 7.1 core semantics:
  public=>1, patterns untrash + publish restore, Home meta upsert,
  check_dependencies ordering, template-part file guard all correct.
  - Applied its single SHOULD-FIX: removed the dead `require_once
    is_plugin_active` block in `check_dependencies()` (`is_plugin_active()` was
    never called; the real gates are the nav-group block and split-section
    pattern registry checks). Re-verified dependency check + full run.

## 2026-09-03 11:46

- Addressed the 4 remaining issues that made the branch an incomplete shell
  replacement:
  1. FRENCH LANGUAGE PACK: the PHP port only set WPLANG (never installed the
     pack; the shell did `wp language core install fr_FR`). Added utility
     `install_language_pack()` using `wp_download_language_pack()` (the no-CLI
     PHP API) — installs fr_FR.mo/admin/network packs into the shared
     WP_LANG_DIR. `provision_language()` now calls it first (correct ordering:
     pack before locale), then WPLANG per site. Logs a clear WARNING if the
     host blocks the download (DISALLOW_FILE_MODS / no write access) so the OVH
     operator installs it manually in Réglages → Langue.
  2. OWNERSHIP/LOCALE ASSUMPTIONS: the admin-locale step used a hardcoded
     `get_user_by('login','admin')`. Now targets the first network super admin
     via `get_super_admins()` (robust to any OVH admin login), falling back to
     the current user. Site ownership (wpmu_create_blog user 1) and TSV
     fixture authorship are unchanged — they match the shell (user 1 is the
     network owner).
  3. HTTP ERROR REPORTING: verified already correct — `handle_run()` wraps
     provision() in `catch (Throwable)`, stores `.error` (empty = success green
     notice, else red notice) + full log in the `lpu_provision_result` site
     option, shown back on the settings page. `Lpu_Provision_Error extends
     Exception` so both expected failures (fail()) and PHP errors reach it.
  4. DEPENDENCY CHECK / OVH DOCS: clarified in code that `check_dependencies()`
     intentionally checks *functionality* (block `lpu/nav-group` and
     `lpu-split-section/*` patterns registered, theme exists) rather than strict
     `is_plugin_active_for_network()` — a plugin active on the main site also
     loads for the network run, so strict network-checking would add a false
     negative. Fixed the README OVH theme-upload menu: the theme goes under
     Apparence → Thèmes → Ajouter → Téléverser (not the plugins menu); plugins
     under Extensions. Updated README language rows (auto now) + step list.
- CLI path on a clean DB:
  Dependencies OK, sub-sites created (public=1), language pack fr_FR installed
  (fr_FR.mo 532 KB on disk), WPLANG=fr_FR everywhere, super-admin locale
  fr_FR, "Provisioning complete".
- Admin HTTP path on a fresh clean DB (no CLI run, authenticated curl):
  login → extract nonce from the network settings page → POST admin-post.php →
  success notice "Provisionnement terminé sans erreur", log ends
  "assembled network Home 6" + "Provisioning complete", stored result
  `error=''`, language pack + WPLANG + public sites all set.
- Render: network home 200 + "Cultiver le vivant"; paris/lyon/marseille 200
  with French core UI strings ("commentaires", "navigation").
- Review subagent on the new language-pack + admin-locale code: no MUST-FIX /
  SHOULD-FIX (correct API, ordering, shared WP_LANG_DIR, robust super-admin
  target, correct network-wide locale semantics).

## 2026-09-03 12:59

- Third review round (4 issues). WIP — most applied, CLI verified.
  1. MENU WORDING: manual language fallback path now "Réglages → Général →
     Langue du site" (was "Réglages → Langue") — README + code.
  2. LANGUAGE PACK FAILURE NOW AN ERROR: `install_language_pack()` returns bool
     (was a dead WARNING that left the green "sans erreur"); `provision_language()`
     `fail()`s with a clear message if the pack can't be auto-installed.
  3. OWNERSHIP PORTABLE: added util helpers `first_super_admin_user()` and
     `default_owner_id()` (first network super admin → current user → 1). Now
     used for `wpmu_create_blog` owner and `upsert_page` default author instead
     of hardcoded user 1; `provision_language()` reuses the same helper
     (removed duplicate super-admin loop).
  4. DEPENDENCY CHECK → NETWORK ACTIVATION: reviewer noted a plugin active only
     on the main site passes the old functional (registry) check but stays off
     the farm sites. Rewrote `check_dependencies()` to require
     `is_plugin_active_for_network()` for nav-group + lpu-split-section. Found
     the shell network-activates them in provision-environment.sh, so added a
     new step `provision_plugins()` that network-activates both (idempotent,
     mirrors network-activate-plugin.sh) BEFORE the check. Removed the
     same-request registry checks (unreliable on first run: a plugin
     network-activated mid-request isn't loaded yet, so lpu/nav-group and
     lpu-split-section/* weren't in the registry -> false failure).
- HTTP hardening: `handle_run()` now registers a shutdown function that writes
  an "interrupted" error + `interrupted=true` flag to the result option if the
  process dies (timeout/OOM/server cut) before the result is stored — covers
  the path that bypasses catch(Throwable).
- CLI **clean normal run on dirty DB**: provision_plugins() network-activated
  both companion plugins, dependencies OK, full provisioning complete.
  Confirmed nav-group/lpu-split-section now active-network.
- NEXT: reset env (announce) and verify BOTH CLI and admin-HTTP paths on a
  fresh DB (incl. fresh-state network-activation of the plugins by
  provision_plugins()), update README/step list, commit, review subagent.

## 2026-09-08

- Investigated the provisioning comparison report. The split-section plugin was
  resolving theme assets with `get_theme_file_uri()` during `init`; on a first
  provisioning request this used the bootstrap theme (often Twenty Twenty-Five)
  even though `lepaysanurbain` was activated later in the same request.
- Fixed the shared asset resolver for the frame catalogue and all split-section
  patterns (`split-content-image`, `split-motif-image`, and
  `split-logo-content`) so they resolve against the `lepaysanurbain` theme
  object directly.
- Verified with `wp lpu provision --force`: the network Home and pattern
  registry now contain LPU theme URLs; the placeholder, logo, and motif assets
  return HTTP 200 and the Home renders the split image.
- Confirmed the Site Editor `/navigation` route is healthy and preloads the
  native `wp_navigation` posts. “Navigation” is the core screen label; the
  provisioned records are titled `Menu principal réseau` / `Menu principal
  ferme` and `Footer réseau` / `Footer ferme`, so no additional navigation
  record is required.
