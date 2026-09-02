nav-group is a custom block. It is a container block (like core/group) designed
grouping native Core Navigation items inside a real `<li><ul>...</ul></li>`
subtree.
Limited in scope : only used at the root of a navigation (menu).

Created so that the theme lepaysanurbain can have a menu with :

- menu elements on the left
- site logo (centered)
- menu elements on the right

An earlier approach used was to have the logo and the menu items flat at the
same level (<li> for the menu items and site logo in the <ul>) but it made
it difficult to make it work with all the required constraints

These are the constraints the menu must have (or had at the time of creating
this custom block)

1. Stay WordPress-native — editable in the admin
2. Logo must be exactly centered, always
3. Top-level items distributed left/right of the logo
4. Items cluster near the logo (gap between items), not hugging container edges
5. No diving into WP core internals in a way that could break on a core update
6. Variable item count per side, editable over time by a non-dev admin
7. mega-menu (submenus) keeps working
8. Mobile overlay stays correct, no duplicated toggle buttons
9. Reusable/maintainable across multiple Multisite network sites
10. Valid HTML markup
11. Possibility to control how the top menu-item are spread in each nav-group:
    Fixed-gap, spaced evenly,..

To avoid a dependency on wp-scripts (which is heavy and represents an added
build step), we avoid using JSX, typescript and other dependencies.
