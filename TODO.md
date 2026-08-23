# TODO

- [ ] in VS Code, make all .php code know about the wordpress source
      instead of the `Undefined function 'add_action'.`
- [ ] Make the build system dynamic so React source files can use clean React/JSX code (e.g. `editor-settings.js`) instead of relying on manually written `createElement()` calls.
- [ ] Review `theme-implementation-plan.md` to identify actions left to implement, problems, and other actionable items.
- [ ] Audit the theme for remaining hard-coded assumptions about the three Paysan Urbain sites.
- [ ] Use https://fullsiteediting.com/lessons/global-style-variations/ to set custom header/menu logo?
- [ ] `plugins/lpu-split-section/lpu-split-section.php` (pattern registration, `NETWORK_LOGO`): the "Côte à côte — logo et titre-texte" pattern hard-codes the network écru logo asset URL (`assets/images/logos/network-horizontal-ecru-baseline.svg`). It is a content placeholder, not part of the header-logo mechanism, but it should become configurable/per-site (e.g. selected from the media library at insertion time).
- [ ] Test a CSS-only replacement for the `.lpu-menu-open` class that `navigation.js` maintains with its MutationObserver. Idea: delete the observer + `syncMenuOpenState()` entirely and rewrite the ~10 `theme.css` rules that use `.lpu-menu-open` with `:has()` selectors instead:
      `body.lpu-header-transparent .lpu-header:has(.wp-block-navigation__responsive-container.is-menu-open)`,
      `body.lpu-header-transparent .lpu-header:has(.wp-block-navigation__submenu-icon[aria-expanded="true"])`,
      `body.lpu-header-transparent .lpu-header:has(.wp-block-navigation-submenu.lpu-hover-bridge-open)`
      (and the `:not(...)` equivalents for the transparent-state rules). Then re-test every menu state: desktop submenu hover + hover-bridge traversal, mobile overlay open/close, keyboard Tab/Escape, and the transparent-header background/color/logo swaps. Only worth doing if the observer proves costly or the JS state ever desynchronizes; `:has()` support is baseline in all evergreen browsers.
