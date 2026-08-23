#!/usr/bin/env bash
set -euo pipefail

# Makes sure the test domains are working.
# They are required for the dev environment to work correctly

hosts=(
  "lepaysanurbain.test"
  "paris.lepaysanurbain.test"
  "lyon.lepaysanurbain.test"
  "marseille.lepaysanurbain.test"
)

missing=()
for host in "${hosts[@]}"; do
  resolved="$(getent ahostsv4 "${host}" 2>/dev/null || true)"
  if ! printf '%s\n' "${resolved}" | rg -q '^127\.0\.0\.1\s'; then
    missing+=("${host}")
  fi
done

if ((${#missing[@]} > 0)); then
  printf 'Missing local host mappings for:\n' >&2
  printf '  %s\n' "${missing[@]}" >&2
  printf '\nAdd this line to /etc/hosts, then run the check again:\n' >&2
  printf '127.0.0.1 %s\n' "${hosts[*]}" >&2
  exit 1
fi

printf 'Local WordPress hostnames resolve to 127.0.0.1.\n'
