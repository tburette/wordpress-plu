# WordPress content provisioning

This directory contains the small, repeatable WP-CLI scripts that create the
content structure needed by the local multisite environment. It is separate
from `scripts/`, which contains environment and network tooling.

Run everything from `site/wordpress-lpu/` with:

```sh
npm run env:content:setup
```

The content provisioning scripts are safe to run repeatedly. Editorial pages
and navigations are not replaced. The managed developer fixtures are explicit
exceptions: `patterns-test-page/` refreshes the exact review-page slug from
the active theme patterns. When a script needs Gutenberg markup or other
input data, those files live beside that script in its own directory. The sole
shared declaration is `home-sections-names.txt`, which records the canonical
section order used by the network Home and its visual test fixture; it is not
a generic shared content directory.

The database remains the runtime copy. The files in this directory are the
reproducible source used when the local WordPress environment is recreated.

The `test-page/` provisioning script restores the development page used to
exercise the theme. It is included in `env:content:setup`, or it can be run on
its own with:

```sh
npm run env:test-page:setup
```

See [`test-page/README.md`](test-page/README.md) for the page details.

The `patterns-test-page/` script is a developer fixture for reviewing the
sections from Fanny's site design in one published page. It assembles the
current Step 5 pattern definitions from WordPress's active theme registry, so
it does not duplicate their markup. It is included in `env:content:setup`, so
the page is created or refreshed automatically when the test environment is
recreated. It can also be run explicitly with:

```sh
npm run env:patterns-test-page:setup
```

See [`patterns-test-page/README.md`](patterns-test-page/README.md) for its
managed-page contract and URL.

The `home-network/` script is the explicit Step 6 provisioning for the network
`Accueil` page. It is deliberately not included in `env:content:setup`, because
that bootstrap must not replace editorial Home content. Run it only after the
front-page and pattern prerequisites have been checked:

```sh
npm run env:home-network:setup
```

See [`home-network/README.md`](home-network/README.md) for its replacement
guard, section transformations and media boundary.

The `navigation-menus/` directory contains the reproducible menu data for the
network site and the three farm sites. Its README documents the relationship
between those files, the shared header, the site-local `wp_navigation` posts,
and the pending visual and accessibility work on the mega-menu. The data files
are deliberately kept beside `navigation-menus/setup.sh`; do not move them to
a shared content directory or hard-code a navigation ID in the theme.

See [`navigation-menus/README.md`](navigation-menus/README.md) before changing
the menu structure or its provisioning.

The `footer-menus/` directory contains the site-local Navigation data used by
the shared footer columns. Run `npm run env:footer-navigation:setup` to create
or select those records without overwriting existing footer content.
