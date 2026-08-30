# TODO

## General

- [ ] in VS Code, make all .php code know about the wordpress source
      instead of the `Undefined function 'add_action'.`
- [ ] Make the build system dynamic so React source files can use clean React/JSX code (e.g. `editor-settings.js`) instead of relying on manually written `createElement()` calls.
- [ ] Review `theme-implementation-plan.md` to identify actions left to implement, problems, and other actionable items.
- [ ] Audit the theme for remaining hard-coded assumptions about the three Paysan Urbain sites.
- [ ] Use https://fullsiteediting.com/lessons/global-style-variations/ to set custom header/menu logo?
- [ ] `plugins/lpu-split-section/lpu-split-section.php` (pattern registration, `NETWORK_LOGO`): the "Côte à côte — logo et titre-texte" pattern hard-codes the network écru logo asset URL (`assets/images/logos/network-horizontal-ecru-baseline.svg`). It is a content placeholder, not part of the header-logo mechanism, but it should become configurable/per-site (e.g. selected from the media library at insertion time).
- [ ] `themes/lepaysanurbain/theme.json` : evalute if settings > color > custom should be true

- [ ] Make content provisioning URL-agnostic and runnable on the target PHP-only OVH Perso hosting installation instead of depending on environment-specific URLs and WP-CLI shell scripts.

## Header/menu

- [ ] Manually validate the minimal `assets/js/navigation.js` collision observer with the network and farm menus: resize through the transition, confirm the inline menu never overlaps the centred logo, and confirm Core still owns the overlay, submenu controls, focus and Escape.
- [ ] Recheck the `:has()` transparent-header state selectors after the adaptive layout observer is validated, especially the logo swap while the overlay or a mega-menu is open.
- [ ] Simplify the transparent-logo implementation in `inc/site-logos.php`; revisit the current rendered-markup injection and determine whether the two logo variants can be represented with simpler native block/template markup. Recheck responsive/raster-logo `srcset` behavior when such assets are introduced.
- is the <!-- wp:site-logo ... /--> needed in `themes/lepaysanurbain/parts/header.html` ? It is not in the database version of the template part, not in the rendered html and the wp_navigation menu-principal is what contains the logo anyway (cf `scripts/content/navigation-menus`).
- `scripts/content/navigation-menus/setup.sh` should always recreate the menu, even if it already exists, instead of re-using the existing one if it is there.
- Nice to have: add a warning somewhere in WP admin when the navigation used by the `header` template part does not contain exactly one `lpu-header__logo` / site-logo item. Ideally show it while editing the navigation menu, but identifying which navigation is used by the `header` template part may be difficult. The requirement is already documented in `documentation utilisateur et technique.md`.

## Fix header center issue

!!!
include :

- use block locking (and templateLock?) on blocks + logo to prevent messing with them
- block_core_navigation_listable_blocks

**#58262** (fusionnée dans Gutenberg 17.7, rétroportée dans WP Core 6.5) déplace la liste des blocs enfants autorisés d'un tableau JS codé en dur vers le champ `allowedBlocks` de `block.json` — extensible via le filtre `register_block_type_args`, confirmé explicitement dans la discussion de la PR.

https://github.com/WordPress/gutenberg/pull/58262#issuecomment-1972709233

**#55551**, fusionnée dans la foulée (même cycle 6.5), résout exactement le problème que j'avais anticipé : elle remplace la logique d'enveloppement en `<li>` — auparavant limitée à une liste figée de blocs (navigation-link, site-logo…) — par un nouveau filtre dédié, `block_core_navigation_listable_blocks`, qui permet à _n'importe quel_ bloc ajouté à `allowedBlocks` de s'enregistrer pour recevoir le même enveloppement `<li>` accessible. Le ticket de suivi montre même un mainteneur Core (ndiego) tester ce scénario précis : un bloc inséré au milieu du menu qui casse la liste en deux `<ul>` — exactement notre cas de figure avec le logo.

https://github.com/WordPress/gutenberg/pull/55551

Concrètement, ça veut dire : **oui, vous pouvez insérer un `core/group` comme enfant direct du bloc Navigation, et lui faire recevoir le même traitement `<li>` que les liens** — via l'API d'extension officielle de Core, pas un contournement.

### Implémentation

```php
add_filter( 'register_block_type_args', function ( $args, $name ) {
    if ( 'core/navigation' === $name ) {
        $args['allowedBlocks'][] = 'core/group';
    }
    return $args;
}, 10, 2 );

add_filter( 'block_core_navigation_listable_blocks', function ( $blocks ) {
    $blocks[] = 'core/group';
    return $blocks;
} );
```

Avec ça, dans l'écran Navigation (le même endroit où l'admin édite déjà son menu unique aujourd'hui), **"Group" apparaît comme bloc insérable**. Vous placez deux Group — un avant `wp:site-logo`, un après — et vous glissez les submenus existants dedans via la Vue Liste. Résultat : un seul menu, édité au même endroit qu'aujourd'hui, mais dont le `<ul>` racine a maintenant trois vrais `<li>` enfants (groupe gauche, logo, groupe droit). La CSS `grid-template-columns: 1fr auto 1fr` que je vous ai donnée s'applique telle quelle, sans DOMDocument, sans parsing côté PHP.

Bonus concret pour l'admin : le bloc Group expose nativement ses propres réglages de mise en page (Flex, justification, espacement) dans le panneau latéral. Le toggle "gap fixe / spread" que le client demande devient un réglage éditorial standard sur chaque groupe, pas une classe CSS à basculer par vous.

### Le seul point à vérifier avant de committer sur cette voie

Les blocs `navigation-link`/`navigation-submenu` sont enveloppés en `<li>` par le _rendu du bloc Navigation lui-même_, quand ils sont ses enfants directs. Une fois nichés à l'intérieur d'un Group — donc à un niveau de plus —, je ne peux pas garantir avec certitude, sans le tester, qu'ils gardent individuellement leur `<li>` (liste imbriquée propre) plutôt que de retomber en simples `<a>` groupés dans le `<div>` du Group (toujours fonctionnel visuellement en flex, mais avec une sémantique de liste un peu dégradée pour ce tronçon). C'est un test de dix minutes en local (wp-env) avant de partir sur cette architecture — pas un doute qui remet en cause l'approche, juste un point à confirmer plutôt que d'affirmer à l'aveugle.

Nécessite WP 6.5+ — non contraignant vu où vous en êtes.

## CSS-only mobile logo fallback (no-JS logo visibility below 600px)

**The problem:**
The current header architecture places the Site Logo block _inside_ the native
Navigation block as a top-level item. This is necessary to achieve the
centered-logo-with-items-on-both-sides layout within a single `wp_navigation`
post. However, WordPress Core Navigation hides its entire closed responsive
container below 600px (`display: none` in `wp-includes/blocks/navigation/style.css`).
Since the logo lives inside that container, **the logo disappears on phones
when JavaScript is disabled or hasn't run yet**.

With JavaScript enabled, `navigation.js` detects that the list is hidden
(zero-width) and forces compact mode, which re-shows the container and the
logo. Without JS, there is no logo at all — just a hamburger button (which
itself is inert without Core's view script). This is a regression from the
previous header design, where the logo was a _sibling_ of the Navigation
block (positioned via CSS Grid on the outer `.lpu-header` container) and
was always visible regardless of viewport width or JS availability.

**The concept (reusing the old design's principle):**
The old header kept the logo outside the Navigation block entirely. The logo
was always rendered, always centered, and always visible — Core's responsive
hiding only affected the navigation, not the logo. The new design can't
restore that exact structure (the logo must be inside the `<ul>` for the
left/right split to work), but it _can_ recover the same _behavior_: the
logo is always visible at every width, purely through CSS, without waiting
for JavaScript.

The approach is to mirror the compact-closed presentation rules that
`navigation.js` applies via the `.lpu-navigation--compact` class, but
under a plain `@media (max-width: 599px)` query that requires no class
at all. This pairs with Core's own `@media (min-width: 600px)` rule that
shows the closed container from 600px upward. Below 600px, our rules take
over: force the container visible, hide non-logo items, and let the logo
show centered. With JS running, `navigation.js` applies the same class and
the same rules activate via the class selectors — no conflict. Without JS,
the media query alone handles it.

**Advantage:**

- The logo is always visible on phones, with or without JavaScript.
- Zero JS dependency for logo visibility.
- No new classes, no PHP changes, no provisioning changes — pure CSS.
- Works with any number of menu items (the logo is the only thing shown).
- Pairs with Core's own 600px breakpoint precisely (Core shows ≥600px,
  this covers <600px).
- The menu itself still requires Core's view script to open (a Core-wide
  limitation for all responsive navigations, not specific to this theme).
  This item only closes the logo gap.

**How it works in detail:**
Core's responsive navigation CSS hides `.wp-block-navigation__responsive-container`
below 600px. The new rules, scoped under `@media (max-width: 599px)`, override
that hiding for the closed state only (`.wp-block-navigation__responsive-container:not(.is-menu-open)`):

1. **Force the container visible**: `display: block; position: static` makes
   the closed container render even below 600px. `position: static` keeps it
   in normal flow so it doesn't overlay content.

2. **Center the item row**: `display: flex; align-items: center; justify-content: center`
   positions the logo in the middle of the header.

3. **Hide non-logo items**: `display: none` on all top-level items except the
   one containing `.lpu-header__logo`. This is the same logic as the compact-closed
   rules in `theme.css:756-770`, just triggered by viewport width instead of
   a JS class.

4. **Show the logo**: The `:has(> .lpu-header__logo)` selector re-shows the
   logo item with `display: block`.

**Where to add it in `themes/lepaysanurbain/assets/css/theme.css`:**
Place it after the existing compact-mode rules (around line 780), before the
`main.wp-block-group` rule. The full CSS to add:

```css
/* No-JS / mobile logo visibility.
 *
 * Below 600px, Core hides the entire closed responsive container (display:none)
 * and only shows it when the overlay is open. Since the logo lives inside that
 * container, it disappears on phones without JavaScript.
 *
 * This media query mirrors the compact-closed presentation unconditionally
 * below Core's 600px breakpoint: force the container visible, hide all
 * non-logo items, and centre the logo. With JS, navigation.js applies
 * .lpu-navigation--compact which activates the same rules via class
 * selectors above; this media query is the CSS-only fallback for the
 * no-JS case and for the brief flash before the deferred script runs.
 *
 * The menu overlay itself still requires Core's Interactivity API view
 * script to open — that is a Core-wide limitation for responsive
 * navigations, not specific to this theme. This rule closes only the
 * logo gap. */
@media (max-width: 599px) {
  .lpu-header .wp-block-navigation__responsive-container:not(.is-menu-open) {
    display: block;
    position: static;
    width: 100%;
    padding: 0;
    background: transparent;
    overflow: visible;
  }

  .lpu-header
    .wp-block-navigation__responsive-container:not(.is-menu-open)
    .wp-block-navigation__responsive-close {
    display: none;
  }

  .lpu-header
    .wp-block-navigation__responsive-container:not(.is-menu-open)
    .wp-block-navigation__responsive-close,
  .lpu-header
    .wp-block-navigation__responsive-container:not(.is-menu-open)
    .wp-block-navigation__responsive-dialog,
  .lpu-header
    .wp-block-navigation__responsive-container:not(.is-menu-open)
    .wp-block-navigation__responsive-container-content {
    position: static;
    width: 100%;
    padding: 0;
  }

  .lpu-header
    .wp-block-navigation__responsive-container:not(.is-menu-open)
    .wp-block-navigation__container {
    display: flex;
    flex-wrap: nowrap;
    align-items: center;
    justify-content: center;
    width: 100%;
    gap: 0;
  }

  .lpu-header
    .wp-block-navigation__responsive-container:not(.is-menu-open)
    .wp-block-navigation__container
    > .wp-block-navigation-item {
    display: none;
  }

  .lpu-header
    .wp-block-navigation__responsive-container:not(.is-menu-open)
    .wp-block-navigation__container
    > .wp-block-navigation-item:has(> .lpu-header__logo) {
    display: block;
  }
}
```

**What it does NOT fix (documented limitations):**

- Without JS, the hamburger button renders but cannot open the overlay
  (Core's open/close is Interactivity API view-script work). The visitor
  sees the logo but cannot access the menu. This is a Core-wide limitation
  for all responsive Navigation blocks, not introduced by this theme.
- The `navigation.js` compact mode below 600px also forces the container
  visible via the `.lpu-navigation--compact` class. When JS is available,
  both the media query and the class apply — they target the same selectors
  with the same declarations, so there is no conflict. The class-based
  rules take precedence at any width; the media query only matters when the
  class is absent (no JS) or before the script runs (flash prevention).
