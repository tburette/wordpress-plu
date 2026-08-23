#!/usr/bin/env bash
set -euo pipefail

# basic checks on multisite configuration

run_wp() {
  wp-env run cli wp "$@"
}

expect_true_wp_constant() {
  local name="$1"
  local value
  value="$(run_wp config get "${name}" --type=constant | tr -d '\r')"
  case "${value,,}" in
    1|true)
      ;;
    *)
      printf '%s must be true; got %s.\n' "${name}" "${value}" >&2
      exit 1
      ;;
  esac
}

expect_true_wp_constant MULTISITE
expect_true_wp_constant SUBDOMAIN_INSTALL

network_meta="$(run_wp network meta get 1 subdomain_install | tr -d '\r')"
if [[ ! "${network_meta,,}" =~ ^(1|true)$ ]]; then
    printf 'Network subdomain_install metadata must be true; got %s.\n' "${network_meta}" >&2
    exit 1
fi

network_domain="$(run_wp config get DOMAIN_CURRENT_SITE --type=constant | tr -d '\r')"
network_path="$(run_wp config get PATH_CURRENT_SITE --type=constant | tr -d '\r')"
# The PHP expressions must remain literal until WP-CLI evaluates them.
# shellcheck disable=SC2016
network_row="$(run_wp eval 'global $wpdb; echo wp_json_encode( $wpdb->get_row( "SELECT id, domain, path FROM {$wpdb->site} WHERE id = 1", ARRAY_A ) );')"
# shellcheck disable=SC2016
main_blog_row="$(run_wp eval 'global $wpdb; echo wp_json_encode( $wpdb->get_row( "SELECT blog_id, domain, path FROM {$wpdb->blogs} WHERE blog_id = 1", ARRAY_A ) );')"
# shellcheck disable=SC2016
blogs="$(run_wp eval 'global $wpdb; echo wp_json_encode( $wpdb->get_results( "SELECT blog_id, domain, path FROM {$wpdb->blogs} WHERE site_id = 1 ORDER BY blog_id", ARRAY_A ) );')"

if [[ "$(jq -r '.domain' <<<"${network_row}")" != "${network_domain}" || "$(jq -r '.path' <<<"${network_row}")" != "${network_path}" ]]; then
  printf 'DOMAIN_CURRENT_SITE/PATH_CURRENT_SITE do not match wp_site.\n' >&2
  exit 1
fi

if [[ "$(jq -r '.domain' <<<"${main_blog_row}")" != "${network_domain}" || "$(jq -r '.path' <<<"${main_blog_row}")" != "${network_path}" ]]; then
  printf 'The main wp_blogs row does not match the network constants.\n' >&2
  exit 1
fi

base_domain="${network_domain#www.}"
for slug in paris lyon marseille; do
  expected_domain="${slug}.${base_domain}"
  if ! jq -e --arg domain "${expected_domain}" --arg path "${network_path}" '.[] | select(.domain == $domain and .path == $path)' <<<"${blogs}" >/dev/null; then
    printf 'Missing or mismatched site: %s.%s%s\n' "${slug}" "${base_domain}" "${network_path}" >&2
    exit 1
  fi
done

printf 'Multisite configuration is consistent.\n'
printf '  network: %s%s\n' "${network_domain}" "${network_path}"
printf '  sites:   %s, %s, %s\n' "paris.${base_domain}" "lyon.${base_domain}" "marseille.${base_domain}"
