# TODO

## General

- [ ] in VS Code, make all .php code know about the wordpress source
      instead of the `Undefined function 'add_action'.`
- [ ] Make the build system dynamic so React source files can use clean React/JSX code (e.g. `editor-settings.js`) instead of relying on manually written `createElement()` calls.
- [ ] Review `theme-implementation-plan.md` to identify actions left to implement, problems, and other actionable items.
- [ ] Audit the theme for remaining hard-coded assumptions about the three Paysan Urbain sites.
- [ ] Use https://fullsiteediting.com/lessons/global-style-variations/ to set custom header/menu logo?
- [ ] `plugins/lpu-split-section/lpu-split-section.php` (pattern registration, `NETWORK_LOGO`): the "Côte à côte — logo et titre-texte" pattern hard-codes the network écru logo asset URL (`assets/images/logos/network-horizontal-ecru-baseline.svg`). It is a content placeholder, not part of the header-logo mechanism, but it should become configurable/per-site (e.g. selected from the media library at insertion time).

- [ ] Make content provisioning URL-agnostic and runnable on the target PHP-only OVH Perso hosting installation instead of depending on environment-specific URLs and WP-CLI shell scripts.

## Header/menu

- [ ] Test a CSS-only replacement for the `.lpu-menu-open` class that `navigation.js` maintains with its MutationObserver. Idea: delete the observer + `syncMenuOpenState()` entirely and rewrite the ~10 `theme.css` rules that use `.lpu-menu-open` with `:has()` selectors instead:
      `body.lpu-header-transparent .lpu-header:has(.wp-block-navigation__responsive-container.is-menu-open)`,
      `body.lpu-header-transparent .lpu-header:has(.wp-block-navigation__submenu-icon[aria-expanded="true"])`,
      `body.lpu-header-transparent .lpu-header:has(.wp-block-navigation-submenu.lpu-hover-bridge-open)`
      (and the `:not(...)` equivalents for the transparent-state rules). Then re-test every menu state: desktop submenu hover + hover-bridge traversal, mobile overlay open/close, keyboard Tab/Escape, and the transparent-header background/color/logo swaps. Only worth doing if the observer proves costly or the JS state ever desynchronizes; `:has()` support is baseline in all evergreen browsers.
- [ ] Replace `lpu_bind_site_navigation()` and the `lpu_navigation_id`/`lpu_footer_navigation_id` runtime lookup with native, site-local Navigation block ownership. Provision the appropriate header and footer template-part/navigation association for each site, then verify that shared theme markup still updates as intended.
- [ ] Remove `lpu_get_navigation_item_count()` and the `lpu-navigation--unsupported-count` fallback once the layout no longer assumes a fixed number of top-level items; arbitrary menu sizes should work through the native Navigation layout.
- [ ] Remove `assets/js/navigation.js`, or reduce it to the smallest narrowly justified enhancement after Core Navigation and the CSS refactor have been tested. Core should remain responsible for menu opening, submenu controls, focus, Escape, and the mobile overlay.
- [ ] Simplify the transparent-logo implementation in `inc/site-logos.php`; revisit the current rendered-markup injection and determine whether the two logo variants can be represented with simpler native block/template markup. Recheck responsive/raster-logo `srcset` behavior when such assets are introduced.
- [ ] Replace the manually recreated hamburger icon with the Core Navigation icon/control once the header layout is stable.
- [ ] Consolidate the responsive breakpoint used by CSS and JavaScript so it has one documented source of truth, or at least document it well. remove the JavaScript dependency on that breakpoint entirely if possible.
