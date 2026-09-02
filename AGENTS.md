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

- the wordpress test environment can we reset at any time meaning the database and files in that environment can go at any moment.
- You can run wordpress with `wp-env` (through `npm run`) and read and interact wordpress (including gutenberg editor pages) with the skill `wordpress-inspector`.
- When you need to reset the wordpress environment (it's not running, see `wp-env status` or it needs to be reset due to changes) : announce the operation to the user then use `npm run cleanup`, `npm run provision`, and `npm run status`. Resetting deletes all local database content therefore never run the reset command autonomously. Confirmation is required.
- If the Docker environment or its generated files are broken, use `npm run cleanup`; it recreates them on the next start while preserving Docker images.
- Docker desktop needs to run. If it doesn't the following message may appear :

```
✖ Error while running docker compose command.
unable to get image 'mariadb:lts': failed to connect to the docker API at unix:///home/tburette/.docker/desktop/docker.sock; check if the path is correct and if the daemon is running: dial unix /home/tburette/.docker/desktop/docker.sock: connect: no such file or directory
```

- WordPress is managed by the globally installed `wp-env` command. Do not add a local `@wordpress/env` dependency.
- To allow user to debug the PHP code, start with `npm run start-xdebug`. Stop the environment first if needed.
- Edit `.wp-env.json` for environment changes; keep project-specific choices in
  that active file.
- Treat `wp-env-options.example.jsonc` as a reference, not a configuration
  that is ever used by wp-env.
- Check `.wp-env.json` before assuming how a directory is mounted or activated.

## WordPress code

- Prioritize native WordPress feature over custom code if possible. Avoid Wordpress working against the way it works
- Code, file names, and CSS classes must be in English; Gutenberg-visible titles and site content must be in French.
- Colors, sizes, and families must derive from `theme.json`, never from arbitrary values added within patterns.
- Use CSS exclusively for styling or processing that `theme.json` cannot properly define.
- Custom javascript is an exception.
- Header navigation menu : the Core Navigation block remains responsible for opening, submenus, focus, Escape key handling, and the mobile overlay.
- A separate online WordPress multisite test installation exists. Link: NOT DISCLOSED YET. The built site must be deployable to this external WordPress installation in order to share it with the client prior to production.

## Collaboration

- Provide regular progress updates, especially during long or complex operations. Basically talk out loud saying what you are thinking. Announce what you are about to do if it involves using tools, skills or the command line.
- Explicitly raise concern when departing from a standard WordPress implementation.
- Ask for confirmation before any major architectural decision, destructive action, or launch of a substantial new workstream.
- In shell tests when `set -o pipefail` is set, `printf ... | rg -q` on long output: `rg -q` can cause the producer to receive `SIGPIPE` and make the pipeline fail even when a match was found.

## Directory structure

This directory is exclusively the WordPress development environment for Le
Paysan Urbain. Work here is limited to WordPress code and development tooling:
the local `wp-env` configuration, themes, plugins, scripts, and documentation
that directly explains them.

There is no wordpress installation here, only theme, plugin, theme.md,....
A real wordpress only exist in the test `wp-env` environment. If you need to
lookup wordpress files, there is a clean, default, WordPress installation in
`/home/tburette/dev/wordpress/wordpress-7.1`. It is unrelated to this project.

The current layout is:

```text
wordpress-lpu/
├── .wp-env.json
├── themes/
│   └── lepaysanurbain/
└── plugins/
    └── example-plugin/
```

## Parent directory

This project is located inside the directory `/home/tburette/dev/lepaysanurbain/`.
That parent directory is itself a project directory; it is for managing work,
communication with clients, organization, todos and the like.
You should not change the files in that parent directory but can read them to
help your work.
Files in `/home/tburette/dev/lepaysanurbain/` are for information only.
Do not act on files in there such as TODO.MD or AGENTS.MD
They must not be treated as a task list for this repository. Do not carry out
parent project-management work or edit parent files unless the user explicitly
requests it.

There could be interesting files for you in it such as:

- `/home/tburette/dev/lepaysanurbain/contextes/` : context of the project,
  homepage structure, menu structure.
- `/home/tburette/dev/lepaysanurbain/design/identité graphique Fanny/Livraison sources Le Paysan Urbain/` : overall design for Le Paysan Urbain
  (design in general, not just the website). with `guide_identite_Le_Paysan_Urbain_regles.txt` (design guide), `Typos Paysan Urbain/` (fonts), `Livraison logos et graphisme/` (icons, illustrations, logos, patterns)
- `/home/tburette/dev/lepaysanurbain/design/design site Fanny/règles-design-site-Le_Paysan_Urbain.txt` : design rules for the website
- `/home/tburette/dev/lepaysanurbain/design/design site Fanny/livraison-des-fichiers-pu_2026-07-30_1227/` : grpahical elements (in the subdirectories), mockups of the website (`menu xxx` for the menu, `Home_1280.jpg` for the network homepage, `Ferme_1280.jpg` for a farm homepage)

# Références visuelles obligatoires

Tout élément créé ou modifié dans le thème — template, template part, pattern,
section, bloc, navigation ou composant visuel — doit chercher à reproduire la
maquette validée de Fanny : composition, proportions, palette, typographie,
espacements, traitements des images, motifs et éléments décoratifs. Une
structure fonctionnelle seule ou un rendu générique WordPress ne constitue pas
une implémentation suffisante.

Les références principales sont les quatre maquettes suivantes, dans le projet
parent :

- `design/design site Fanny/livraison-des-fichiers-pu_2026-07-30_1227/Ferme_1440.jpg`
- `design/design site Fanny/livraison-des-fichiers-pu_2026-07-30_1227/Home_1440.jpg`
- `design/design site Fanny/livraison-des-fichiers-pu_2026-07-30_1227/menu Home_1440  (transparent).jpg`
- `design/design site Fanny/livraison-des-fichiers-pu_2026-07-30_1227/menu Ferme_1440 (fond écru).jpg`

Il existe également le répertoire
`design/design site Fanny/livraison-des-fichiers-pu_2026-07-30_1227/sections/`
qui contient les différentes sections de la maquette individuellement dans
une image qui leur est propre.

Avant de considérer un travail visuel terminé, le comparer au visuel de
référence approprié avec une inspection rendue du site et signaler tout écart
important.

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
