#!/bin/bash
# Install a project-local composer.phar into bin/.
# Useful when the system composer is too old to resolve modern dependencies
# (common on shared hosting).
#
# Usage:
#   bin/install-composer.sh
#
# After install, invoke composer like:
#   php8.X bin/composer.phar install     # X is any installed PHP 8.2+

set -euo pipefail

DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# Fetch the Composer installer script and verify its checksum.
EXPECTED_CHECKSUM="$(php -r 'copy("https://composer.github.io/installer.sig", "php://stdout");')"
php -r "copy('https://getcomposer.org/installer', '$DIR/composer-setup.php');"
ACTUAL_CHECKSUM="$(php -r "echo hash_file('sha384', '$DIR/composer-setup.php');")"

if [ "$EXPECTED_CHECKSUM" != "$ACTUAL_CHECKSUM" ]; then
    echo 'ERROR: installer checksum mismatch' >&2
    rm -f "$DIR/composer-setup.php"
    exit 1
fi

# Install into bin/.
php "$DIR/composer-setup.php" --quiet --install-dir="$DIR" --filename=composer.phar
rm -f "$DIR/composer-setup.php"

echo "Installed: $DIR/composer.phar"
"$DIR/composer.phar" --version
