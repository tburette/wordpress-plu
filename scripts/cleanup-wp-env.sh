#!/usr/bin/env bash
set -euo pipefail

# Remove the generated wp-env containers, volumes, networks, and local files
# while preserving Docker images. The next `npm run env:start` recreates the
# environment without downloading the images again.
script_dir="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd -P)"
project_dir="$(cd -- "${script_dir}/.." && pwd -P)"

cat >&2 <<'EOF'
WARNING: this will clean the local wp-env environment.

It removes this project's WordPress and test containers, Docker volumes,
networks, and generated local wp-env files. Database data inside this
environment will be lost. Docker images and project source files are kept.
EOF

read -r -p "Continue? Type yes to proceed [yes/no]: " confirmation
case "${confirmation,,}" in
  yes)
    ;;
  *)
    echo "Cancelled. Nothing was cleaned."
    exit 0
    ;;
esac

cd -- "${project_dir}"
wp-env cleanup

