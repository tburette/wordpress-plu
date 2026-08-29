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

- [ ] Manually validate the minimal `assets/js/navigation.js` collision observer with the network and farm menus: resize through the transition, confirm the inline menu never overlaps the centred logo, and confirm Core still owns the overlay, submenu controls, focus and Escape.
- [ ] Recheck the `:has()` transparent-header state selectors after the adaptive layout observer is validated, especially the logo swap while the overlay or a mega-menu is open.
- [ ] Simplify the transparent-logo implementation in `inc/site-logos.php`; revisit the current rendered-markup injection and determine whether the two logo variants can be represented with simpler native block/template markup. Recheck responsive/raster-logo `srcset` behavior when such assets are introduced.
- is the <!-- wp:site-logo ... /--> needed in `themes/lepaysanurbain/parts/header.html` ? It is not in the database version of the template part, not in the rendered html and the wp_navigation menu-principal is what contains the logo anyway (cf `scripts/content/navigation-menus`).
- `scripts/content/navigation-menus/setup.sh` should always recreate the menu, even if it already exists, instead of re-using the existing one if it is there.
- Nice to have: add a warning somewhere in WP admin when the navigation used by the `header` template part does not contain exactly one `lpu-header__logo` / site-logo item. Ideally show it while editing the navigation menu, but identifying which navigation is used by the `header` template part may be difficult. The requirement is already documented in `documentation utilisateur et technique.md`.
