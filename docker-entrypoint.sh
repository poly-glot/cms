#!/bin/sh
set -eu

if [ "${SKIP_MIGRATIONS:-0}" != "1" ]; then
    bin/cake migrations migrate
fi

exec "$@"
