#!/usr/bin/env bash
set -euo pipefail

# Install and activate the French core language pack for every site, then set
# the default local admin user's profile locale so wp-admin is French even
# when the user has an explicit locale preference.

script_dir="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd -P)"
project_dir="$(cd -- "${script_dir}/.." && pwd -P)"
locale="fr_FR"
admin_user="admin"

cd -- "${project_dir}"

run_wp() {
  wp-env run cli wp "$@" </dev/null
}

if ! run_wp language core is-installed "${locale}" >/dev/null 2>&1; then
  run_wp language core install "${locale}" >/dev/null
fi

site_urls="$(run_wp site list --network=1 --field=url | tr -d '\r')"
if [[ -z "${site_urls}" ]]; then
  printf 'No WordPress sites found; cannot activate language %s.\n' "${locale}" >&2
  exit 1
fi

while IFS= read -r site_url; do
  [[ -z "${site_url}" ]] && continue
  run_wp site switch-language "${locale}" --url="${site_url}" >/dev/null
  printf '%s: language %s active\n' "${site_url}" "${locale}"
done <<< "${site_urls}"

# WordPress uses a user's explicit locale in preference to the site's locale.
# The default wp-env account is named "admin" (see README.md).
run_wp user update "${admin_user}" --locale="${locale}" --skip-email >/dev/null
printf '%s: user locale %s\n' "${admin_user}" "${locale}"
