#!/usr/bin/env bash

DB_DIRECTORY=$(mktemp -d)
SOCKET=$(mktemp -d)/mysqld.sock

# Set up the server
mkdir -p "$DB_DIRECTORY"
chmod 750 "$DB_DIRECTORY"
mysql_install_db --datadir="$DB_DIRECTORY" > /dev/stderr

# Start the server
mysqld_safe --datadir="$DB_DIRECTORY" --socket="$SOCKET" --skip-networking --no-auto-restart > /dev/stderr

# Create users
sleep 2 # Waiting does not seem to work.
mysql --wait --socket="$SOCKET" -e "CREATE USER 'selfoss'@'localhost' IDENTIFIED BY 'password';" > /dev/stderr
mysql --wait --socket="$SOCKET" -e "CREATE DATABASE selfoss;" > /dev/stderr
mysql --wait --socket="$SOCKET" -e "GRANT ALL PRIVILEGES ON *.* TO 'selfoss'@'localhost';" > /dev/stderr

echo "$SOCKET"

echo 'Use `mysqladmin --socket="$SOCKET" shutdown` to stop the database server:' > /dev/stderr
echo "mysqladmin --socket=\"$SOCKET\" shutdown" > /dev/stderr

