#!/bin/sh

set -eu

SUDO=""
if [ "$(id -u)" -ne 0 ]; then
    SUDO="sudo"
fi

install_gd_extension() {
    if php -m | grep -qi '^gd$'; then
        echo "GD extension already enabled."
        return 0
    fi

    echo "GD extension not detected. Installing..."

    if command -v apt-get >/dev/null 2>&1; then
        $SUDO apt-get update -y
        if apt-cache show php-gd >/dev/null 2>&1; then
            $SUDO apt-get install -y php-gd
        else
            PHP_MM="$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')"
            $SUDO apt-get install -y "php${PHP_MM}-gd"
        fi
    elif command -v dnf >/dev/null 2>&1; then
        $SUDO dnf install -y php-gd
    elif command -v yum >/dev/null 2>&1; then
        $SUDO yum install -y php-gd
    elif command -v apk >/dev/null 2>&1; then
        PHP_MM="$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')"
        $SUDO apk add --no-cache "php${PHP_MM}-gd" || $SUDO apk add --no-cache php-gd
    elif command -v zypper >/dev/null 2>&1; then
        $SUDO zypper --non-interactive install php8-gd || $SUDO zypper --non-interactive install php-gd
    else
        echo "Could not detect package manager to install php-gd automatically."
        echo "Install GD manually, then re-run this script."
        exit 1
    fi

    if php -m | grep -qi '^gd$'; then
        echo "GD extension installed successfully."
    else
        echo "GD package installed but extension not active in CLI yet."
        echo "Restart PHP/FPM service and re-run this script if needed."
    fi
}

install_gd_extension

EXPECTED_SIGNATURE=$(wget -q -O - https://composer.github.io/installer.sig)
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
ACTUAL_SIGNATURE=$(php -r "echo hash_file('SHA384', 'composer-setup.php');")

if [ "$EXPECTED_SIGNATURE" != "$ACTUAL_SIGNATURE" ]
then
    >&2 echo 'ERROR: Invalid installer signature'
    rm composer-setup.php
    exit 1
fi

php composer-setup.php --quiet
RESULT=$?
rm composer-setup.php

mv composer.phar composer

exit $RESULT