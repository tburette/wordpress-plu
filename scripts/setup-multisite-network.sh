#!/usr/bin/env bash
set -euo pipefail

# setup the multisite network only
# check hosts, align network metadata, create subsites

script_dir="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd -P)"
project_dir="$(cd -- "${script_dir}/.." && pwd -P)"
network_domain="lepaysanurbain.test"

cd -- "${project_dir}"

run_wp() {
  wp-env run cli wp "$@"
}

bash "${script_dir}/check-local-hosts.sh"
run_wp core is-installed --network >/dev/null

run_wp eval 'if ( ! is_multisite() || ! is_subdomain_install() ) { fwrite( STDERR, "WordPress is not configured as a subdomain multisite.\n" ); exit( 1 ); }'

# wp-env installs the network before applying SUBDOMAIN_INSTALL. Keep the
# network metadata aligned before creating any child sites.
run_wp network meta update 1 subdomain_install 1 >/dev/null
run_wp option update blogname "Le Paysan Urbain" >/dev/null

sites=(
  "paris|Le Paysan Urbain Paris"
  "lyon|Le Paysan Urbain Lyon"
  "marseille|Le Paysan Urbain Marseille"
)

for definition in "${sites[@]}"; do
  IFS='|' read -r slug title <<<"${definition}"
  existing_urls="$(run_wp site list --network=1 --field=url)"

  # already in place
  if printf '%s\n' "${existing_urls}" | rg -Fq -- "//${slug}.${network_domain}"; then
    continue
  fi

  # create subsite
  run_wp site create \
    --slug="${slug}" \
    --title="${title}" \
    --email="wordpress@example.com" \
    --porcelain >/dev/null
done
