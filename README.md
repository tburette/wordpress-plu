# Le Paysan Urbain WordPress development environment

This repository is the local WordPress development environment for Le Paysan
Urbain. It is intentionally focused on WordPress code and tooling: the
network, themes, plugins, scripts, and developer documentation that supports
them.

The environment is a fresh WordPress Multisite network. It currently contains
the network site and three local farm sites:

| Site | Local URL |
| --- | --- |
| Network | `http://lepaysanurbain.test:8888` |
| Paris | `http://paris.lepaysanurbain.test:8888` |
| Lyon | `http://lyon.lepaysanurbain.test:8888` |
| Marseille | `http://marseille.lepaysanurbain.test:8888` |

The port is fixed because it is part of the effective local WordPress host.
Changing it after the network has been created is a domain migration, not just
a web-server setting.

## Prerequisites

Install and start Docker, then install the globally managed `wp-env` command:

```sh
npm install --global @wordpress/env
wp-env --version
```

This repository does not install `@wordpress/env` locally. There is no
project-local `npm install` step for the WordPress environment.

### Local hostnames

The subdomains must resolve to the local Docker port. Add this line to
`/etc/hosts` once:

```text
127.0.0.1 lepaysanurbain.test paris.lepaysanurbain.test lyon.lepaysanurbain.test marseille.lepaysanurbain.test
```

Check the mappings with:

```sh
npm run env:hosts:check
```

## Start the environment

From this directory:

```sh
npm run env:start
npm run env:status
```

The `afterStart` lifecycle script in `.wp-env.json` then:

1. checks the local hostnames;
2. confirms that WordPress is a network;
3. aligns the network `subdomain_install` metadata with the
   `SUBDOMAIN_INSTALL` constant;
4. creates the Paris, Lyon, and Marseille sites when they are missing; and
5. installs and activates the French (`fr_FR`) core language pack for all four
   sites and sets the default `admin` user's locale to French;
6. provisions all required and developer content from the co-located files in
   `scripts/content/`, including the static `Accueil` pages, logos, site-local
   header/footer Navigations, test pages, the all-patterns review page, and the
   network Home fixture; then
7. verifies the network and site domain/path records.

The bootstrap is safe to run repeatedly:

```sh
npm run env:multisite:setup
npm run env:multisite:verify
```

The default credentials are `admin` / `password`.

The bootstrap keeps the local WordPress administration in French. To apply the
language setup independently to an existing environment, run:

```sh
npm run env:language:setup
```

## Reset the local database

To test provisioning scripts from a clean WordPress database without
recreating the Docker environment, use:

```sh
npm run env:reset
npm run env:multisite:setup
npm run env:status
```

The reset deletes local WordPress content and multisite records, but preserves
the containers, volumes, images, mounted source, and configuration. The
confirmation prompt is intentional.

If the generated environment itself is broken, use:

```sh
npm run env:cleanup
npm run env:start
```

This removes its containers, volumes, networks, and generated files while
preserving Docker images.

## Multisite configuration

The active configuration is `.wp-env.json`. Important settings include:

- `"multisite": true` asks `wp-env` to run the multisite installation;
- `"SUBDOMAIN_INSTALL": true` configures subdomain behavior at runtime;
- `WP_SITEURL` and `WP_HOME` use the stable local network domain;
- `SCRIPT_DEBUG: true` keeps WordPress and editor assets unminified for
  readable development stack traces;
- `WP_DEVELOPMENT_MODE: "theme"` enables theme-related development behavior,
  including the relevant theme and pattern cache handling;
- `port: 8888` and `autoPort: false` keep the WordPress host stable;
- `testsEnvironment: false` keeps one development environment only;
- `plugins` is empty for now; `themes` mounts the versioned
  `themes/lepaysanurbain` source directory.

`wp-env` generates the multisite constants such as `MULTISITE`,
`DOMAIN_CURRENT_SITE`, and `PATH_CURRENT_SITE` during the fresh installation.
The setup script also updates the network metadata used for the subdomain
choice before it creates child sites.

`WP_ALLOW_MULTISITE` is not required for this automated setup. It only enables
the manual Network Setup screen in the WordPress administration interface.

`SAVEQUERIES` is intentionally not enabled by default because it retains every
database query in memory on each request. Enable it temporarily when profiling,
or use Query Monitor for targeted query inspection. Likewise,
`WP_DISABLE_FATAL_ERROR_HANDLER` and direct `ini_set( 'display_errors', '1' )`
are not part of the default configuration: the former removes WordPress's
recovery behavior and the latter can corrupt HTML, AJAX, or JSON responses.

When changing the network topology or base domain, clean the local generated
environment and recreate it. Do not edit the generated `wordpress/` tree or
manually change only one of the network records:

```sh
npm run env:cleanup
npm run env:start
```

The cleanup command asks for confirmation, preserves Docker images, and
removes only this project's environment. It does not remove unrelated Docker
projects.

## WordPress CLI

Use the npm wrapper for commands against the development container:

```sh
npm run env:cli -- core is-installed --network
npm run env:cli -- site list --fields=blog_id,domain,path,url
npm run env:cli -- theme list
npm run env:cli -- plugin list
npm run env:cli -- option get home
```

The direct form is also available:

```sh
wp-env run cli wp site list
```

## Themes and plugins

Theme and plugin source must live outside the generated `wordpress/` tree. The
current layout is:

```text
wordpress-lpu/
├── .wp-env.json
├── themes/
│   └── lepaysanurbain/
└── plugins/
    └── example-plugin/
```

`wp-env` mounts themes but does not activate them automatically. Activate the
theme on the required network sites with WP-CLI after it has been mounted.
The multisite bootstrap does this through `scripts/setup-theme.sh`; run it
manually with `npm run env:theme:setup` when needed.

Plugins can be mounted and activated when listed in `.wp-env.json`:

```json
"plugins": ["./plugins/example-plugin"]
```

### Content provisioning

Scripts that create WordPress content live in `scripts/content/`. Each script
keeps its Gutenberg markup and other data in the same directory, while
`scripts/content/setup.sh` provides the single dispatcher for the whole set:

```sh
# Provision everything, in dependency order.
npm run env:content

# Provision one or more selected operations.
npm run env:content -- patterns-test-page
npm run env:content -- home-network navigation-menus

# Intentionally replace an already assembled network Home.
npm run env:content -- home-network --force
```

With no selector, the command provisions logos, front pages, header and footer
Navigations, the typography test page, the all-patterns review page at
`/lpu-sections-patterns-test/`, and the network Home fixture. The review page
contains every pattern supplied by the active theme and is separate from the
Home. Ordinary editorial pages and Navigations are preserved; the managed test
pages are refreshed according to their script contracts, and the Home refuses
to replace non-placeholder content unless `--force` is supplied.

The front-page operation remains technical provisioning: it selects or creates
a Page with the `accueil` slug on each site, points the site's reading settings
to it, and gives a newly created Page an invisible placeholder comment. It does
not add patterns, images, or sections from the design maquettes. The Home
operation stores its block markup in the network `Accueil` page, resolves the
three farm URLs from the current multisite, and enables the transparent-header
option.

Pattern source files stay generic so that an editor can reuse them on any page.
When a pattern is provisioned into a page, the content scripts add the same
root block metadata that Gutenberg adds during manual insertion
(`patternName`, pattern title and category). The network Home fixture then
applies its own explicit demonstration copy and farm URLs to those generic
slots. This keeps the Gutenberg inserter generic while preserving a
reproducible Home composition; later editorial changes should be made in
Gutenberg and are protected from replacement unless `--force` is supplied.

### Shared header and navigation

The four sites use one shared `parts/header.html` template part and one core
Navigation block per site. The navigation content is site-local and is
provisioned from `scripts/content/navigation-menus/`; a numeric Navigation
`ref` must never be copied from one site to another.

The target desktop composition is a full-width header with the horizontal logo
centered and the links on both sides. There is no hamburger on large screens.
A submenu is the expanded version of the same Navigation block: a contextual
mega-menu below the header, with an écru background and readable columns. It
must be reachable by click and keyboard focus; hover may be an additional
desktop shortcut. The hamburger is reserved for the responsive overlay on
tablet and mobile.

The current theme files are still the Step 3 scaffold: they do not yet prove
the final centered geometry, mega-menu presentation, transparent-header toggle,
or complete keyboard and pointer behavior. Those points must be tested and
implemented before Step 3 is validated.

The opaque écru header is the default. A transparent header over a sufficiently
contrasted hero is an explicit per-page option, and an opened mega-menu always
returns the header and panel to opaque écru. The screenshots in the design
delivery show a desktop hamburger and `Agir avec nous`; those are historical
maquette elements and are not part of the current menu contract.

The complete menu contract, source priorities, current limitations and pending
checks are documented in the comments at the top of
`scripts/content/navigation-menus/setup.sh` and in
[`theme-implementation-plan.md`](theme-implementation-plan.md).

## VS Code

Open the supplied workspace file:

```sh
code wordpress-scaffold.code-workspace
```

It opens the project root, excludes generated WordPress files from search, and
marks the local `wordpress/` tree read-only. Opening the folder directly also
works, but the workspace is the preferred entry point. Additional theme,
plugin, or external WordPress core folders can be added to its `folders` list.

## Xdebug

Start with Xdebug enabled:

```sh
npm run env:start:xdebug
npm run env:xdebug:patch
```

Then launch `wp-env listen for XDebug` in VS Code. The patch script discovers
the current generated WordPress source path and updates the core mapping while
preserving more-specific theme or plugin mappings.

Do not assume a fixed Docker container name. Use `wp-env status`, Docker's
container list, or the provided path-mapping script.

## Stop and inspect

```sh
npm run env:status
npm run env:logs
npm run env:stop
```

`env:stop` preserves the database and generated environment. `env:cleanup`
removes the generated environment and its local database after confirmation,
while preserving Docker images.

## Generated files

The local `wordpress/` directory is scratch/generated state and is ignored by
Git. It can be recreated at any time. Never put project source inside it.
