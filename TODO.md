# TODO

## General

- [x] `lpu_split_section_register_patterns()` runs on every request by design. `register_block_pattern()` populates WordPress's in-memory, request-scoped registry; an activation hook runs only once and cannot make the patterns available to later frontend, editor, REST, or WP-CLI requests. Network activation makes the plugin load for each site, while each request registers its own site's patterns.
- [ ] lpu_split_section_theme_asset_uri : `plugins/lpu-split-section/lpu-split-section.php:29 (lpu_split_section_theme_asset_uri)`.
      Is it me or that code is ugly. I mean, forcing using another theme like that from a plugin??
      Isn't there a way to run the code after the theme has changed?
- [ ] Use https://fullsiteediting.com/lessons/global-style-variations/ to set custom header/menu logo?
- [ ] `plugins/lpu-split-section/lpu-split-section.php` (pattern registration, `NETWORK_LOGO`): the "Côte à côte — logo et titre-texte" pattern hard-codes the network écru logo asset URL (`assets/images/logos/network-horizontal-ecru-baseline.svg`). It is a content placeholder, not part of the header-logo mechanism, but it should become configurable/per-site (e.g. selected from the media library at insertion time).
- [ ] `themes/lepaysanurbain/theme.json` : evalute if settings > color > custom should be true
- [ ] provisioning is slow. I think it might be because every command goes through "wp-env run cli wp". Which incures a cost. Maybe we could put the script (and associated content) in the "cli" environment and run the provisioning scripts there directly, thus skipping the wp-env run cost.
- [ ] Make content provisioning URL-agnostic and runnable on the target PHP-only OVH Perso hosting installation instead of depending on environment-specific URLs and WP-CLI shell scripts.
- [x] remove duplication : "${script_dir}/setup-split-plugin.sh" and "${script_dir}/setup-nav-group-plugin.sh"
- [ ] Ajouter les pastilles. Doit pouvoir configurer leur apparence (couleur, contenu,..). Doit pouvoir ajouter "librement". (attention mobile)
- [ ] in the templates, do we need the wp:group wrapping the wp:post-content? Might be standard (because there is a <main> there) or might be superfluous
- [ ] lpu/split-section and lpu/split-zone are missing many options in gutenberg that are available in native blocks such as wp:group. If I understand correctly to add them you have to add elements to 'supports' when calling register_block_type (PHP) and registerBlockType (js).
      At first I thought it's odd to add only top/bottom margin support, there should be for all sides. Thinking more about it, I realize there are many options missing!
      I see that there a quit a lot of them : https://developer.wordpress.org/block-editor/reference-guides/block-api/block-supports/.
      I'm not sure which should be enabled for each. Please make suggetion for both. which support, what it does (very very succintly). Also explain why to add or not add the support (small argumentation).
- [ ] When two lpu/split-section (Section côte à côte) are one after another it is.. very ugly. There is zero space between the two (as I actually wanted, what with blockGap 0 and all). However here it's ugly (one pattern turns into another one uglily) and hard to understand (when same background color : they meld one to another). should probably also check other combinations.
- [ ] In "http://lepaysanurbain.test:8888/" (Accueil network?). The lepaysanurbain/columns (Colonnes de texte) containing the text "Un message commun à faire vivre" has humongous margins. We'll have to figure out why.
- [ ] Check if there is a gap between header <=> main <=> footer, see how it
      has been implemented and check if it is ok or there is a cleaner way.

      Context: `wp:post-content` now has `blockGap: 0px`, which removes the
      default space between page sections placed directly in the page content.
      This raised the question of the gap between  `header`, `main`, and
      `footer` blocks in a template. WordPress renders the header and footer as
      `core/template-part` blocks, which do not expose the native spacing
      controls which is availablein in a container  block.
      The theme has two targeted CSS rules for the transparent-header-to-content
      and content-to-footer boundaries:
      1. When the header is transparent and positioned over the page, remove the top margin of main so no empty strip appears below the header.
         themes/lepaysanurbain/assets/css/theme.css L411
         ```css
         /* The transparent header is removed from normal flow, so the block-theme
          * site-stack gap must not leave an écru strip before the hero. */
         body.lpu-header-transparent .wp-site-blocks > main.wp-block-group {
           margin-block-start: 0;
         }
         ```
      2. When the footer directly follows main, remove the default WordPress margin before the footer so it touches the last content section.
         themes/lepaysanurbain/assets/css/theme.css L
         ```css
         /* The footer follows the last editorial section directly. WordPress adds its
          * normal site-stack gap to every sibling; that creates an unintended écru
          * strip after a full-width closing band. */
         .wp-site-blocks > main + footer.wp-block-template-part {
           margin-block-start: 0;
         }
         ```

      Options:
      - Native container: wrap the header, main, and footer in one Group whose
        Block spacing is set to `0px`, then verify that the semantic header,
        main, and footer markup, transparent header, and responsive behavior
        remain correct. Remove the CSS exceptions if the wrapper handles all
        three boundaries reliably.
      - Keep the targeted CSS: retain the current rules because they directly
        handle boundaries involving `core/template-part`, where Gutenberg does
        not currently provide an equivalent spacing control.
      - Hybrid: use native spacing for the boundaries that belong to a Group,
        and retain a small CSS exception for any boundary involving a
        `core/template-part` that still cannot be controlled natively.

```html
<!-- wp:group {"tagName":"main","className":"template-page-group"} -->
<main class="wp-block-group template-page-group">
  <!-- wp:post-content {"className":"template-page-post-content","style":{"spacing":{"blockGap":"var:preset|spacing|60"}},"layout":{"type":"constrained"}} /-->
</main>
<!-- /wp:group -->
```

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

## CSS

- [ ] rationalize box-sizing
      list which elements usr border/content box
      find content:
      ```js
      function auditBoxSizing() {

        const borderBox = [];
        const contentBox = [];

        document.querySelectorAll('*').forEach(el => {
          const value = getComputedStyle(el).boxSizing;
          if (value === 'border-box') {
            borderBox.push(el);
          } else if (value === 'content-box') {
            contentBox.push(el);
          }
        });

        console.log(`border-box: ${borderBox.length} elements`, borderBox);
        console.log(`content-box: ${contentBox.length} elements`, contentBox);

        return { borderBox, contentBox };
      }
      auditBoxSizing();
      ```

      highlight visually the border-box
      ```js
      function highlightMismatches() {
        document.querySelectorAll('*').forEach(el => {
          const own = getComputedStyle(el).boxSizing;
          const parent = el.parentElement && getComputedStyle(el.parentElement).boxSizing;
          if (parent && own !== parent) {
            el.style.outline = '2px solid orange';
            el.style.outlineOffset = '-2px';
          }
        });
      }
      ```
