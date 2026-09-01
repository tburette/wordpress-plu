# Nav Group spike

Temporary test block for checking whether native Navigation blocks can be grouped inside a real `<li><ul>...</ul></li>` subtree.

## Test in WordPress

1. The plugin is mounted by `.wp-env.json`.
2. Activate **Nav Group (TEST)** if it is not already active.
3. Create a temporary Page.
4. Add a **Navigation** block.
5. Inside Navigation, add **Nav Group (TEST)**.
6. Inside Nav Group, add two **Navigation Link** blocks.
7. Add one normal **Navigation Link** directly to Navigation, outside the group.
8. Publish/view the page.
9. Inspect the rendered DOM in browser DevTools.

Expected structure:

```html
<ul class="wp-block-navigation__container">
  <li class="wp-block-navigation-item lpu-nav-group">
    <ul class="lpu-nav-group__list">
      <li class="wp-block-navigation-item wp-block-navigation-link">...</li>
      <li class="wp-block-navigation-item wp-block-navigation-link">...</li>
    </ul>
  </li>
  <li class="wp-block-navigation-item wp-block-navigation-link">...</li>
</ul>
```

The important observation is that the grouped Navigation Links remain `<li>` elements under a nested `<ul>`, rather than becoming siblings of the outer navigation items.

This is a throwaway spike; do not use it in the production header/menu yet.
