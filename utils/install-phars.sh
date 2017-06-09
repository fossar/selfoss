#!/bin/sh

install_phar() {
    mkdir -p "$HOME/.local/bin"
    curl -L "$1" -o "$HOME/.local/bin/$2"
    chmod +x "$HOME/.local/bin/$2"
}

install_phar "https://github.com/fossar/PHP-Parallel-Lint/releases/download/v0.9.2/parallel-lint.phar" php-parallel-lint
install_phar "https://github.com/FriendsOfPHP/PHP-CS-Fixer/releases/download/v2.8.3/php-cs-fixer.phar" php-cs-fixer
