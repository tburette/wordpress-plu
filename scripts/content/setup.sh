#!/usr/bin/env bash
set -euo pipefail

# Provision the minimum WordPress content needed by the local multisite
# network. The network topology is created by the parent script in
# scripts/setup-multisite-network.sh; this directory only handles content.
#
# Content provisioning is intentionally idempotent: missing records and
# settings may be created, but content already edited in WordPress is not
# reset. Each provisioning script keeps its input files next to itself.
script_dir="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd -P)"

bash "${script_dir}/front-pages/setup.sh"
bash "${script_dir}/navigation-menus/setup.sh"
bash "${script_dir}/test-page/setup.sh"
