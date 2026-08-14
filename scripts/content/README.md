# WordPress content provisioning

This directory contains the small, repeatable WP-CLI scripts that create the
content structure needed by the local multisite environment. It is separate
from `scripts/`, which contains environment and network tooling.

Run everything from `site/wordpress-lpu/` with:

```sh
npm run env:content:setup
```

The content provisioning scripts are safe to run repeatedly. They do not
replace existing WordPress content. When a script needs Gutenberg markup or
other input data, those files live beside that script in its own directory;
there is no shared data directory.

The database remains the runtime copy. The files in this directory are the
reproducible source used when the local WordPress environment is recreated.

The `test-page/` provisioning script restores the development page used to
exercise the theme. It is included in `env:content:setup`, or it can be run on
its own with:

```sh
npm run env:test-page:setup
```

See [`test-page/README.md`](test-page/README.md) for the page details.

The `patterns-test-page/` script is a separate developer fixture for reviewing
the sections from Fanny's site design in one published page. It assembles the
current Step 5 pattern definitions from WordPress's active theme registry, so
it does not duplicate their markup. Run it explicitly with:

```sh
npm run env:patterns-test-page:setup
```

See [`patterns-test-page/README.md`](patterns-test-page/README.md) for its
managed-page contract and URL.

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
