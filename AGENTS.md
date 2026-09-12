# AGENTS.md

This file contains project constraints for coding agents.

## Wordpress test environment and Provisioning script

wp-env is used (`.wp-env.json`). This means there is no regular wordpress installation in this directory.
The test environment is a WordPress Multisite network. It currently contains
the network site and three local farm sites:

| Site      | Local URL                                   |
| --------- | ------------------------------------------- |
| Network   | `http://lepaysanurbain.test:8888`           |
| Paris     | `http://paris.lepaysanurbain.test:8888`     |
| Lyon      | `http://lyon.lepaysanurbain.test:8888`      |
| Marseille | `http://marseille.lepaysanurbain.test:8888` |

The wp admin credentials are `admin` / `password`.

- the wordpress test environment can be reset at any time meaning the database and files in that environment can go at any moment.
- You can interact with wp-env using `wp-env` (or through one of the `npm run` commands).
- You can interact with wordpress using `npm run wp` (which is `wp-env run cli wp`).
- You can look at the content and absence of errors in a post (page, template, navigation,..) with the skill `wordpress-inspector`.
- There are bind mounts so that the files in the plugins (`plugins/`) and the theme (`themes/lepaysanurbain/`) in the test environment are always the current versions. No need to reset the environment to take into account change in those files. -Check `.wp-env.json` to see how a directory is mounted or activated.
- You may need to reset the wordpress environment. Resetting deletes all local database content therefore never run the reset command autonomously. Confirmation is required. For example : provisioning must re-run, the database must be re-created, it's broken.. To reset : announce the operation to the user, use `npm run cleanup` then `npm run provision`. Use `npm run status` if needed.
- The following error means you must ask the user to start docker desktop :

```
✖ Error while running docker compose command.
unable to get image 'mariadb:lts': failed to connect to the docker API at unix:///home/tburette/.docker/desktop/docker.sock; check if the path is correct and if the daemon is running: dial unix /home/tburette/.docker/desktop/docker.sock: connect: no such file or directory
```

-The globally installed `wp-env` command is used. Do not add a local `@wordpress/env` dependency or use npx.

## WordPress code

- Prioritize native WordPress feature over custom code if possible. Avoid Wordpress working against the way it works
- Code, file names, and CSS classes must be in English; Gutenberg-visible titles and site content must be in French.
- Colors, sizes, and families must derive from `theme.json`, never from arbitrary values added within patterns.
- Use CSS exclusively for styling or processing that `theme.json` cannot properly define.
- Custom javascript is an exception.
- Header navigation menu : the Core Navigation block remains responsible for opening, submenus, focus, Escape key handling, and the mobile overlay.
- A separate online WordPress multisite test installation exists. Link: NOT DISCLOSED YET. The built site must be deployable to this external WordPress installation in order to share it with the client prior to production.

## Collaboration

- Provide regular progress updates, especially during long or complex operations. Basically talk out loud saying what you are thinking as you work. Announce what you are about to do if it involves using tools, skills or the command line.
- Ask for confirmation before any major architectural decision, destructive action, or launch of a substantial new workstream.
- In shell tests when `set -o pipefail` is set, `printf ... | rg -q` on long output: `rg -q` can cause the producer to receive `SIGPIPE` and make the pipeline fail even when a match was found.

## Directory structure

This directory is exclusively the WordPress development environment for Le
Paysan Urbain. Work here is limited to WordPress code and development tooling:
the local `wp-env` configuration, themes, plugins, scripts, and their
documentation.

There is no wordpress installation here, only theme, plugin, theme.md,....
A real wordpress only exist in the test `wp-env` environment. If you need to
lookup wordpress files, there is a clean, default, read-only WordPress
installation in `/home/tburette/dev/wordpress/wordpress-7.1`.
It is unrelated to this project.

The current layout is:

```text
wordpress-lpu/
├── .wp-env.json
├── package.json
├── themes/
│   └── lepaysanurbain/
└── plugins/
    ├── lpu-provisioning
    ├── lpu-split-section
    └── nav-group
```

Ignore the files `Accueil.html`, `Sections du design de Fanny.html` and `themes/lepaysanurbain/assets/css/theme.annotated.css`. They are temporary duplicates files that help user.

## Parent directory

This project is located inside the directory `/home/tburette/dev/lepaysanurbain/`.
That parent directory is for managing work,
communication with clients, organization, todos and the like, design documents.
Do not edit files in that parent `/home/tburette/dev/lepaysanurbain/` the user explicitly requested or agreed to it. The files in it are there
to provide information.
Do not act on files in there such as its todo.

There could be interesting files for you in it such as:

- `/home/tburette/dev/lepaysanurbain/contextes/` : context of the project,
  homepage structure, menu structure.
- `/home/tburette/dev/lepaysanurbain/design/identité graphique Fanny/Livraison sources Le Paysan Urbain/` : overall design for Le Paysan Urbain
  (design in general, not just the website). with `guide_identite_Le_Paysan_Urbain_regles.txt` (design guide), `Typos Paysan Urbain/` (fonts), `Livraison logos et graphisme/` (icons, illustrations, logos, patterns)
- `/home/tburette/dev/lepaysanurbain/design/design site Fanny/règles-design-site-Le_Paysan_Urbain.txt` : design rules for the website
- `/home/tburette/dev/lepaysanurbain/design/design site Fanny/livraison-des-fichiers-pu_2026-07-30_1227/` : grpahical elements (in the subdirectories), mockups of the website (`menu xxx` for the menu, `Home_1280.jpg` for the network homepage, `Ferme_1280.jpg` for a farm homepage)

# Mandatory visual references

Every element created or modified in the theme — template, template part,
pattern, section, block, navigation, or visual component — must seek to
reproduce Fanny's approved mockup: composition, proportions, palette,
typography, spacing, image treatments, patterns, and decorative elements. A
functional structure alone or a generic WordPress design does not constitute
a sufficient implementation.

The main visual references are the following four mockups:

(`design/` is in `/home/tburette/dev/lepaysanurbain/`)

- `design/design site Fanny/livraison-des-fichiers-pu_2026-07-30_1227/Ferme_1440.jpg` : mockup of homepage for a farm subsite
- `design/design site Fanny/livraison-des-fichiers-pu_2026-07-30_1227/Home_1440.jpg` : mockup of the homepage for the root (network) site
- `design/design site Fanny/livraison-des-fichiers-pu_2026-07-30_1227/menu Home_1440  (transparent).jpg` : menu/header (transparent version)
- `design/design site Fanny/livraison-des-fichiers-pu_2026-07-30_1227/menu Ferme_1440 (fond écru).jpg` : normal menu/header

The directory
`design/design site Fanny/livraison-des-fichiers-pu_2026-07-30_1227/sections/`,
contains the different sections of the mockups above split in their own images.

`design/design site Fanny/règles-design-site-Le_Paysan_Urbain.txt` detailed textual explanation
of the desired website design. Include links to files: logos, patterns, fonts,...

Before considering work involving visual design of an element touching the aboce, compare it with the appropriate
reference image by inspecting the rendered site and report any significant
discrepancy.

## Git repository

This directory is its **own git repository**, nested inside a larger
local-only management repository at `/home/tburette/dev/lepaysanurbain/`.

- **Remote**: `git@github.com:tburette/wordpress-plu.git` (private).
- **Purpose**: intra-day backup of the WordPress code so no work is lost. Commit
  often, ideally on every meaningful change:
  `git add -A && git commit`.
- **Scope**: WordPress code development. The parent repository holds the rest of
  the project (design mockups, PDFs, client documents, invoices, and so on) and is
  never pushed to GitHub.
- **Why nested**: git discovers the repository by walking up to the nearest
  `.git`. Commands run from here target this repo; commands run above `site/`
  target the parent management repo. Do not `git init` again and do not add the
  parent repo as a remote.
- The parent repo deliberately tracks some of these files too (double tracking,
  an accepted choice). Do not try to "fix" that duplication.
- Make sure to be in the `wordpress-plu` directory (`pwd`) when running git
  commands.

## $website-visual-diff skill

if the website-visual-diff is used but no
URL has been provided, use the URL
http://lepaysanurbain.test:8888/lpu-sections-patterns-test/ by default.
