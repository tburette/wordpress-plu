#!/usr/bin/env bash
set -euo pipefail

# Provision WordPress content for the local multisite network.
#
# With no arguments this runs every content operation in dependency order.
#
# Pass one or more directory names to run only selected operations, for example:
#   npm run env:content -- patterns-test-page
#   npm run env:content -- home-network navigation-menus
#
# The network and active theme must already exist. 
script_dir="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd -P)"

usage() {
  cat <<'USAGE'
Usage: npm run env:content [-- [operation ...]]

Run every content operation when no operation is provided. Otherwise choose
one or more of:

  site-logos
  front-pages
  navigation-menus
  footer-menus
  test-page
  patterns-test-page
  home-network

Use --force with home-network to intentionally replace an already assembled
network Home:

  npm run env:content -- home-network --force
USAGE
}

operations=(
  site-logos
  front-pages
  navigation-menus
  footer-menus
  test-page
  patterns-test-page
  home-network
)

if [[ "${1:-}" == "--help" || "${1:-}" == "-h" ]]; then
  usage
  exit 0
fi

if [[ "${1:-}" == "--" ]]; then
  shift
fi

force=false
selected_operations=()
for argument in "$@"; do
  if [[ "${argument}" == "--force" ]]; then
    force=true
    continue
  fi

  if [[ "${argument}" == "all" ]]; then
    selected_operations+=("${operations[@]}")
    continue
  fi

  case " ${operations[*]} " in
    *" ${argument} "*) selected_operations+=("${argument}") ;;
    *)
      printf 'Unknown content operation: %s\n\n' "${argument}" >&2
      usage >&2
      exit 1
      ;;
  esac
done

if [[ "${#selected_operations[@]}" -eq 0 ]]; then
  selected_operations=("${operations[@]}")
fi

for operation in "${selected_operations[@]}"; do
  printf '\n==> Provisioning %s\n' "${operation}"
  if [[ "${operation}" == "home-network" && "${force}" == true ]]; then
    bash "${script_dir}/${operation}/setup.sh" --force
  elif [[ "${operation}" != "home-network" && "${force}" == true ]]; then
    printf 'The --force option is only supported for home-network.\n' >&2
    exit 1
  else
    bash "${script_dir}/${operation}/setup.sh"
  fi
done

printf '\nContent provisioning complete.\n'
