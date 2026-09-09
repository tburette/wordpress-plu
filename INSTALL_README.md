# Le Paysan Urbain WordPress development environment

This repository is the local WordPress development environment for Le Paysan
Urbain. It is intentionally focused on WordPress code and tooling: the
network, themes, plugins, scripts, and developer documentation that supports
them.

The test environment is a WordPress Multisite network. It currently contains
the network site and three local farm sites:

| Site      | Local URL                                   |
| --------- | ------------------------------------------- |
| Network   | `http://lepaysanurbain.test:8888`           |
| Paris     | `http://paris.lepaysanurbain.test:8888`     |
| Lyon      | `http://lyon.lepaysanurbain.test:8888`      |
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
npm run check-local-hosts
```

## Start the environment

From this directory:

```sh
npm run start
npm run status
```

Starting the environment only starts the local WordPress containers. Activate
the provisioning plugin once, then run its provisioning command when the
environment is new or after its local database has been reset:

```sh
npm run wp -- plugin activate lpu-provisioning --network
npm run provision
npm run verify-multisite
```

The provisioning plugin:

1. aligns the multisite network metadata and creates missing farm sites;
2. activates the theme on the network sites;
3. installs and activates the French (`fr_FR`) core language pack;
4. installs and network-activates Query Monitor when it is absent, then
   activates the local plugins;
5. provisions logos, pages, navigations, template parts, test pages, and the
   network Home from `plugins/lpu-provisioning/content/`, replacing its
   current content on each run.

Provisioning can be run repeatedly; the network Home is reconstructed from the
source content each time. The verification command can be run independently:

```sh
npm run verify-multisite
```

#### Provisioning after `npm run start`

`npm run provision`

The default credentials are `admin` / `password`.

Provisioning keeps the local WordPress administration in French. There are no
separate shell provisioning commands; rerun the plugin command when applying
the configuration again.

## Reset the local database

To test the provisioning plugin from a clean WordPress database without
recreating the Docker environment, use:

```sh
npm run reset
npm run wp -- plugin activate lpu-provisioning --network
npm run provision
npm run status
```

The reset deletes local WordPress content and multisite records, but preserves
the containers, volumes, images, mounted source, and configuration. The
confirmation prompt is intentional.

If the generated environment itself is broken, use:

```sh
npm run cleanup
npm run start
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

`wp-env` generates the multisite constants such as `MULTISITE`,
`DOMAIN_CURRENT_SITE`, and `PATH_CURRENT_SITE` during the fresh installation.
The provisioning plugin updates the network metadata used for the subdomain
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
npm run cleanup
npm run start
```

The cleanup command asks for confirmation, preserves Docker images, and
removes only this project's environment. It does not remove unrelated Docker
projects.

## WordPress CLI

Use the npm wrapper for commands against the development container:

```sh
npm run wp -- core is-installed --network
npm run wp -- site list --fields=blog_id,domain,path,url
npm run wp -- theme list
npm run wp -- plugin list
npm run wp -- option get home
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

`wp-env` mounts themes but does not activate them automatically. The
provisioning plugin activates the mounted theme on the required network sites.

Plugins can be mounted and activated when listed in `.wp-env.json`:

```json
"plugins": ["./plugins/example-plugin"]
```

### Content provisioning

The provisioning plugin is the single entry point for the site configuration
and developer fixtures. Its source data lives beside the plugin in
`plugins/lpu-provisioning/content/` and is applied in dependency order:

```sh
npm run provision
```

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
npm run start-xdebug
```

it will trigger a patch script with `npm run patch-xdebug`.

Then launch `wp-env listen for XDebug` in VS Code (F5). The patch script
discovers the current generated WordPress source path and updates the core
mapping while preserving more-specific theme or plugin mappings.

Do not assume a fixed Docker container name. Use `wp-env status`, Docker's
container list, or the provided path-mapping script.

## Stop and inspect

```sh
npm run status
npm run logs
npm run stop
```

`stop` preserves the database and generated environment. `cleanup` removes the
generated environment and its local database after confirmation, while
preserving Docker images.
