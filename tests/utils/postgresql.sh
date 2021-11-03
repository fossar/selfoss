#!/usr/bin/env bash

DB_DIRECTORY=$(mktemp -d)
SOCKET_DIR=$(mktemp -d)

postgresOptions=(
	-k "$SOCKET_DIR"
	-c "listen_addresses="
)

# Set up the server
mkdir -p "$DB_DIRECTORY"
chmod 750 "$DB_DIRECTORY"
initdb "$DB_DIRECTORY" > /dev/stderr

# Start the server
pg_ctl start -D "$DB_DIRECTORY" -o "${postgresOptions[*]}" > /dev/stderr # Intentionally passing options as a string

# Create users
# Using a “template1” database since it is guaranteed to be present.
psql -h "$SOCKET_DIR" -d template1 -tAc 'CREATE USER "selfoss"' > /dev/stderr
psql -h "$SOCKET_DIR" -d template1 -tAc 'CREATE DATABASE "selfoss" WITH OWNER = "selfoss"' > /dev/stderr

echo "$SOCKET_DIR"

echo 'Use `pg_ctl stop -D "$(psql -h "$SOCKET_DIR" -d template1 -tAc "SHOW data_directory;")"` to stop the database server:' > /dev/stderr
echo "pg_ctl stop -D \"$DB_DIRECTORY\"" > /dev/stderr
