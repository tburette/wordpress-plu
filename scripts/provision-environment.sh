#!/usr/bin/env bash
set -euo pipefail

# provision the development site
# multisite network, language, theme, plugins, content, then verify

script_dir="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd -P)"

bash "${script_dir}/setup-multisite-network.sh"
bash "${script_dir}/setup-language.sh"
bash "${script_dir}/setup-theme.sh"
bash "${script_dir}/setup-split-plugin.sh"
bash "${script_dir}/setup-nav-group-plugin.sh"
bash "${script_dir}/content/setup.sh"
bash "${script_dir}/verify-multisite-network.sh"
