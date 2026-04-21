#!/usr/bin/env sh

if [ -x ./vendor/bin/sail ]; then
    exec ./vendor/bin/sail "$@"
fi

echo "Sail is not installed. Run 'composer install' first." >&2
exit 1
