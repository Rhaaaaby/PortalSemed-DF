#!/bin/sh
set -eu

load_secret() {
    variable_name="$1"
    secret_file="$2"

    if [ ! -r "$secret_file" ]; then
        echo "Required secret file is not readable: $secret_file" >&2
        exit 1
    fi

    secret_value=$(cat "$secret_file")
    if [ -z "$secret_value" ]; then
        echo "Required secret file is empty: $secret_file" >&2
        exit 1
    fi

    export "$variable_name=$secret_value"
}

load_secret DB_PASS "${DB_PASS_FILE:?DB_PASS_FILE is required}"
load_secret SMTP_PASSWORD "${SMTP_PASSWORD_FILE:?SMTP_PASSWORD_FILE is required}"

exec /usr/local/bin/docker-php-entrypoint "$@"
