#!/usr/bin/env bash
set -euo pipefail

# wp-env reset of the environment gated by user confirmation
# resets database but not files
script_dir="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd -P)"
project_dir="$(cd -- "${script_dir}/.." && pwd -P)"

cat >&2 <<'EOF'
WARNING: this will reset the local development WordPress database.

All local WordPress content will be deleted: posts, pages, media, users,
options, menus, and multisite site records. Project source files and Docker
containers, volumes, and images are preserved.
EOF

read -r -p "Continue? Type yes to proceed [yes/no]: " confirmation
case "${confirmation,,}" in
  yes)
    ;;
  *)
    echo "Cancelled. Nothing was reset."
    exit 0
    ;;
esac

cd -- "${project_dir}"
wp-env reset development

