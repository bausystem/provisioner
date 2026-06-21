#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd -P)"
ENV_FILE="$SCRIPT_DIR/.env"

if [ ! -f "$ENV_FILE" ]; then
  echo "Missing environment file: $ENV_FILE"
  exit 1
fi

set -a
source "$ENV_FILE"
set +a

if [ -z "${REMOTE_URL:-}" ] || [ -z "${BRANCH:-}" ]; then
  echo "Environment file must define REMOTE_URL and BRANCH: $ENV_FILE"
  exit 1
fi

cd "$SCRIPT_DIR"

if [ -d ".git" ]; then
  echo "A .git directory already exists here. Refusing to reinitialize it."
  exit 1
fi

if git init --initial-branch="$BRANCH" >/dev/null 2>&1; then
  :
else
  git init
  git branch -M "$BRANCH"
fi

git remote add origin "$REMOTE_URL"

echo "Git has been initialized."
echo "Branch: $BRANCH"
echo "Remote origin: $REMOTE_URL"
echo
echo "Next useful commands:"
echo "  git status"
echo "  git fetch origin"
