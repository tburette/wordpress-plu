# AGENTS.md

This file contains project constraints for coding agents. The user-facing
setup and workflow documentation lives in `README.md`.

## Test des scripts de provisioning

- Avant de tester ou de retester les scripts qui créent ou configurent du contenu WordPress, repartir d’une base locale vierge. Prévenir de l’opération et obtenir confirmation, puis utiliser `npm run env:reset`, `npm run env:multisite:setup` et `npm run env:status`. Le reset supprime tout le contenu de la base locale, mais conserve les conteneurs, volumes, images et sources ; un nouveau lancement sur une base existante ne constitue qu’un test d’idempotence.
- Si l’environnement Docker ou ses fichiers générés sont défectueux, utiliser `npm run env:cleanup` : cette commande les recrée au prochain démarrage tout en conservant les images Docker.

## Repository scope

This directory is exclusively the WordPress development environment for Le
Paysan Urbain. Work here is limited to WordPress code and development tooling:
the local `wp-env` configuration, themes, plugins, scripts, and documentation
that directly explains them.

This project is located inside the directory `/home/tburette/dev/lepaysanurbain/`.
that parent directory itself a project directory, it is about managing work,
communication with clients, organization, todos and the like. There are files in there
such as
`/home/tburette/dev/lepaysanurbain/AGENTS.md`,
`/home/tburette/dev/lepaysanurbain/todo.txt` and
`/home/tburette/dev/lepaysanurbain/contextes/` that could give contextual information.
They must not be treated as a task list for this repository. Do not carry out
parent project-management work or edit parent files unless the user explicitly
requests it as part of a WordPress development change.

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

## Xdebug

- Start with `wp-env start --xdebug` or `npm run env:start:xdebug`.
- Run `bash scripts/update-xdebug-path-mapping.sh` (or
  `npm run env:xdebug:patch`) before launching the VS Code debugger.
- Do not assume the container is named `wp-env-$(basename "$PWD")`. Compose
  adds the generated project hash, service name, and instance suffix. Use the
  script or the diagnostic commands in `README.md` to find the real instance.

## Do not

- Create themes or plugins inside `wordpress/wp-content/`.
- Write project code within the local `wordpress/` tree or wordpress installation. It can be deleted any
  time and would result in data loss.
- add data that exist only in the WordPress database and would be lost when the installation is deleted.

## Reference

Read `README.md` for prerequisites, plugin/theme/workspace setup, Xdebug,
formatter behavior, CLI workflows, and the tests-environment explanation.
