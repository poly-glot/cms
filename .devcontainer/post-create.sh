#!/usr/bin/env bash
set -euo pipefail

echo "=== CakePHP CMS Development Container Setup ==="

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
WORKSPACE="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$WORKSPACE"

# Verify core tooling
echo ""
echo "=== Installed versions ==="
php --version
composer --version
git --version
mysql --version

# Configure git
git config --global core.autocrlf input
git config --global init.defaultBranch main
git config --global --add safe.directory "$WORKSPACE"

# Wait for MySQL to be reachable before we scaffold (DSN config below depends on it)
echo ""
echo "[post-create] Waiting for MySQL..."
for i in {1..30}; do
  if mysqladmin ping -h db -ucms -pcms --silent 2>/dev/null; then
    echo "[post-create] MySQL is up."
    break
  fi
  sleep 2
done

# Scaffold a fresh CakePHP 5 app if the workspace looks empty (no composer.json yet)
if [ ! -f "$WORKSPACE/composer.json" ]; then
  echo ""
  echo "[post-create] Scaffolding new CakePHP 5 app..."
  # create-project requires an empty target dir; .devcontainer/.idea are fine.
  composer create-project --no-interaction --prefer-dist cakephp/app:~5.0 /tmp/cakephp-scaffold
  shopt -s dotglob
  # Move everything except the .devcontainer and .idea folders into the workspace
  for entry in /tmp/cakephp-scaffold/*; do
    name="$(basename "$entry")"
    if [ "$name" = ".devcontainer" ] || [ "$name" = ".idea" ]; then
      continue
    fi
    mv "$entry" "$WORKSPACE/"
  done
  shopt -u dotglob
  rm -rf /tmp/cakephp-scaffold

  # Point CakePHP at the docker-compose MySQL service
  if [ -f "$WORKSPACE/config/app_local.php" ]; then
    sed -i "s/'host' => 'localhost'/'host' => '127.0.0.1'/g" "$WORKSPACE/config/app_local.php"
    sed -i "s/'username' => 'my_app'/'username' => 'cms'/g" "$WORKSPACE/config/app_local.php"
    sed -i "s/'password' => 'secret'/'password' => 'cms'/g" "$WORKSPACE/config/app_local.php"
    sed -i "s/'database' => 'my_app'/'database' => 'cms'/g" "$WORKSPACE/config/app_local.php"
    sed -i "s/'database' => 'test_myapp'/'database' => 'cms_test'/g" "$WORKSPACE/config/app_local.php"
  fi

else
  echo "[post-create] composer.json found — skipping scaffold, running composer install."
  composer install --no-interaction --prefer-dist
fi

# Patch config/app_local.php every time — composer install regenerates it from the example
# with localhost defaults, and PHP's MySQL driver treats localhost as a Unix socket.
if [ -f "$WORKSPACE/config/app_local.php" ]; then
  sed -i "s/'host' => 'localhost'/'host' => '127.0.0.1'/g" "$WORKSPACE/config/app_local.php"
  sed -i "s/'username' => 'my_app'/'username' => 'cms'/g" "$WORKSPACE/config/app_local.php"
  sed -i "s/'password' => 'secret'/'password' => 'cms'/g" "$WORKSPACE/config/app_local.php"
  sed -i "s/'database' => 'my_app'/'database' => 'cms'/g" "$WORKSPACE/config/app_local.php"
  sed -i "s/'database' => 'test_myapp'/'database' => 'cms_test'/g" "$WORKSPACE/config/app_local.php"
fi

# Ensure the test database exists (idempotent)
mysql -h db -uroot -proot -e "CREATE DATABASE IF NOT EXISTS cms_test; GRANT ALL ON cms_test.* TO 'cms'@'%';" 2>/dev/null || true

# Install Playwright CLI + Chromium (PLAYWRIGHT_BROWSERS_PATH set in Dockerfile)
echo ""
echo "[post-create] Installing Playwright + Chromium..."
export PLAYWRIGHT_BROWSERS_PATH=/ms-playwright
if ! command -v playwright >/dev/null 2>&1; then
  # sudo's secure_path strips the Node feature's bin dir; pass PATH through explicitly.
  sudo env "PATH=$PATH" npm install -g @playwright/test playwright
fi
# Globs in `[ ]` aren't expanded — use compgen-style check.
if ! ls -d "$PLAYWRIGHT_BROWSERS_PATH"/chromium-* >/dev/null 2>&1; then
  playwright install chromium
fi
playwright --version

# Aliases
cat >> /home/vscode/.zshrc << 'ALIASES'

# Claude shortcut
alias c="claude"

# CakePHP shortcuts
alias cake="bin/cake"
alias cserve="bin/cake server -H 0.0.0.0 -p 8765"
alias cmigrate="bin/cake migrations migrate"
alias crollback="bin/cake migrations rollback"
alias cbake="bin/cake bake"

# Composer
alias ci="composer install"
alias cu="composer update"
alias cr="composer require"

# Git shortcuts
alias gs="git status"
alias gd="git diff"
alias gl="git log --oneline -20"
alias gp="git push"

# MySQL shortcut
alias dbcli="mysql -h db -ucms -pcms cms"

# Playwright
export PLAYWRIGHT_BROWSERS_PATH=/ms-playwright
alias pw="playwright"
alias pwtest="playwright test"
alias pwcodegen="playwright codegen http://localhost:8765"
alias pwopen="playwright open http://localhost:8765"
ALIASES

echo ""
echo "=== Setup complete ==="
echo "Run 'cserve' to start the CakePHP dev server on http://localhost:8765"
echo "Run 'dbcli' to open a MySQL shell against the cms database."
