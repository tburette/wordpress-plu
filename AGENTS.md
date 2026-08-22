# AGENTS.md

This file contains project constraints for coding agents. The user-facing
setup and workflow documentation lives in `README.md`.

## Provisioning script tests

- Before testing or retesting scripts that create or configure WordPress content, start from a clean local environment. Announce the operation then use `npm run env:cleanup`, `npm run env:multisite:setup`, and `npm run env:status`. Resetting deletes all local database content; never run the reset command autonomously. Confirmation is required.
- If the Docker environment or its generated files are broken, use `npm run env:cleanup`; it recreates them on the next start while preserving Docker images.

## Implementation plan

- Follow the steps in `theme-implementation-plan.md` in order, one step at a time.
- Do not consider a step complete until its planned validation has been performed; do not start the next step before that validation.
- Keep one clearly identified current step in the plan's progress section. Remaining tasks from a previous step are prerequisites to finish, not a second current step.
- If a technical constraint requires deviating from this order, explain it before proceeding and ask for confirmation.

## Collaboration

- Provide regular progress updates, especially during long or complex operations.
- Explicitly flag blockers, uncertainties, and choices that depart from a standard WordPress implementation.
- Clearly distinguish what has been verified from what is assumed, and state test limitations.
- Ask for confirmation before any major architectural decision, destructive action, or launch of a substantial new workstream.
- Ask for visual, editorial, or functional verification when those aspects cannot be validated from code alone.
- In shell tests using `set -o pipefail`, avoid `printf ... | rg -q` on long output: `rg -q` can cause the producer to receive `SIGPIPE` and make the pipeline fail even when a match was found. Prefer a direct search or a form that consumes all output.

## Repository scope

This directory is exclusively the WordPress development environment for Le
Paysan Urbain. Work here is limited to WordPress code and development tooling:
the local `wp-env` configuration, themes, plugins, scripts, and documentation
that directly explains them.

This project is located inside the directory `/home/tburette/dev/lepaysanurbain/`.
That parent directory is itself a project directory; it is for managing work,
communication with clients, organization, todos and the like. There are files in there
such as
`/home/tburette/dev/lepaysanurbain/AGENTS.md`,
`/home/tburette/dev/lepaysanurbain/todo.txt` and
`/home/tburette/dev/lepaysanurbain/contextes/` that could give contextual information.
They must not be treated as a task list for this repository. Do not carry out
parent project-management work or edit parent files unless the user explicitly
requests it as part of a WordPress development change.

## Git repository

This directory is its **own git repository**, nested inside a larger
local-only management repository at `/home/tburette/dev/lepaysanurbain/`.

- **Remote**: `git@github.com:tburette/wordpress-plu.git` (private).
- **Purpose**: intra-day backup of the WordPress code so no work is lost. Push
  often, ideally on every meaningful change:
  `git add -A && git commit && git push`.
- **Scope**: WordPress code development. The parent repository holds the rest of
  the project (design mockups, PDFs, client documents, invoices, and so on) and is
  never pushed to GitHub because it is too large and too sensitive; GitHub cannot
  back up a subdirectory of a repository anyway.
- **Why nested**: git discovers the repository by walking up to the nearest
  `.git`. Commands run from here target this repo; commands run above `site/`
  target the parent management repo. Do not `git init` again and do not add the
  parent repo as a remote.
- The parent repo deliberately tracks some of these files too (double tracking,
  an accepted choice). Do not try to "fix" that duplication.
- Make sure to be in the `wordpress-plu` directory (`pwd`) when running git
  commands.

## Essential project context

- WordPress is managed by the globally installed `wp-env` command. Do not add
  a local `@wordpress/env` dependency.
- The baseline uses `"core": null`, empty `plugins` and `themes` arrays, and
  `WP_ENVIRONMENT_TYPE: "development"`.
- Keep custom themes and plugins outside any local `wordpress/` directory.
- Edit `.wp-env.json` for environment changes; keep project-specific choices in
  that active file.
- Treat `wp-env-options.example.jsonc` as a reference, not a configuration
  that is ever used by wp-env.
- Check `.wp-env.json` before assuming how a directory is mounted or activated.

## Références visuelles obligatoires

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

## Codex and wp-env access

- Direct `wp-env` commands may require Codex authorization for Docker access
  and may fail. Prefer the project scripts `npm run env:*` when available.

## Xdebug

- Start with `npm run env:start:xdebug` (it will run the required
  `bash scripts/update-xdebug-path-mapping.sh` via `npm run env:xdebug:patch`).
  Restart the environment if needed.

## Do not

- Create themes or plugins inside `wordpress/wp-content/`.
- Write project code within the local `wordpress/` tree or WordPress installation. It can be deleted at any
  time and doing so would result in data loss.
- Add data that exists only in the WordPress database and would be lost when the installation is deleted.

## Reference

Read `README.md` for prerequisites, plugin/theme/workspace setup, Xdebug,
formatter behavior, CLI workflows, and the tests-environment explanation.
