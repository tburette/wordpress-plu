# TODO

## General

- [ ] Use https://fullsiteediting.com/lessons/global-style-variations/ to set custom header/menu logo?
- [ ] `plugins/lpu-split-section/lpu-split-section.php` (pattern registration, `NETWORK_LOGO`): the "Côte à côte — logo et titre-texte" pattern hard-codes the network écru logo asset URL (`assets/images/logos/network-horizontal-ecru-baseline.svg`). It is a content placeholder, not part of the header-logo mechanism, but it should become configurable/per-site (e.g. selected from the media library at insertion time).
- [ ] `themes/lepaysanurbain/theme.json` : evalute if settings > color > custom should be true
- [ ] provisioning is slow. I think it might be because every command goes through "wp-env run cli wp". Which incures a cost. Maybe we could put the script (and associated content) in the "cli" environment and run the provisioning scripts there directly, thus skipping the wp-env run cost.
- [ ] Make content provisioning URL-agnostic and runnable on the target PHP-only OVH Perso hosting installation instead of depending on environment-specific URLs and WP-CLI shell scripts.
- [x] remove duplication : "${script_dir}/setup-split-plugin.sh" and "${script_dir}/setup-nav-group-plugin.sh"
- Ajouter les pastilles. Doit pouvoir configurer leur apparence (couleur, contenu,..). Doit pouvoir ajouter "librement". (attention mobile)

## A tester

- Tester explicitement l’ajout et la suppression d’un site de ferme, puis vérifier que le thème et les autres sites continuent à fonctionner sans nouvelle branche de code liée à un nom comme `paris`, `lyon` ou `marseille`. (en profiter pour documenter, voir `documentation utilisateur et technique.md`).
- Vérifier la configurabilité du plugin `lpu-split-section`. Les couleurs et les motifs (cadres) ne doivent pas être figés dans le code du plugin ni par une liste numérique de motifs (`array( 1, 2, 3, 4, 5, 7, 8 )`).

## Header/menu

- [ ] Intermediary layout for the desktop header menu : text and logo a bit smaller to fit longer in available width. Make the menu a bit wider too?
- [ ] when showing the header menu in the editor (admin). The logo is invisible. Only happens when "Faire un lien vers l’accueil sur l’image" is checked. the `<a>` has `display: inline-block;`. it makes the img (and a) of the logo invisible. I think due to : <a> says the size depends on content and <img> says the size depends on parent (% in width). Result : size is 0. Thought it was because of theme.css being applied in the editor but no, even disabling `add_action('enqueue_block_assets', 'lpu_enqueue_theme_styles');` in functions.php does not change things.
- [ ] Manually validate the minimal `assets/js/navigation.js` collision observer with the network and farm menus: resize through the transition, confirm the inline menu never overlaps the centred logo, and confirm Core still owns the overlay, submenu controls, focus and Escape.
- [ ] Recheck the `:has()` transparent-header state selectors after the adaptive layout observer is validated, especially the logo swap while the overlay or a mega-menu is open.
- [ ] Simplify the transparent-logo implementation in `inc/site-logos.php`; revisit the current rendered-markup injection and determine whether the two logo variants can be represented with simpler native block/template markup. Recheck responsive/raster-logo `srcset` behavior when such assets are introduced.
- [ ] @is the <!-- wp:site-logo ... /--> needed in `themes/lepaysanurbain/parts/header.html` ? It is not in the database version of the template part, not in the rendered html and the wp_navigation menu-principal is what contains the logo anyway (cf `scripts/content/navigation-menus`).
- [ ] `scripts/content/navigation-menus/setup.sh` should always recreate the menu, even if it already exists, instead of re-using the existing one if it is there.
- [ ] Nice to have: add a warning somewhere in WP admin when the navigation used by the `header` template part does not contain exactly one `lpu-header__logo` / site-logo item. Ideally show it while editing the navigation menu, but identifying which navigation is used by the `header` template part may be difficult. The requirement is already documented in `documentation utilisateur et technique.md`.
- [ ] There has got to be a way to merge our compact (see css+navigation.js) with the native mobile layout.
      questions :
  - Is the native mobile layout really ever activated? Doesn't compact always run before the native mobile menu layout can run?
  - The native mobile layout doesn't have the logo in the middle. How is the site logo rendered in compact mode?

- [ ] use block locking (and templateLock?) on blocks + logo to prevent messing with them?
