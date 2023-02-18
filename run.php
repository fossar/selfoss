<?php

declare(strict_types=1);

// Only run this if running from PHP embedded server.
if (PHP_SAPI === 'cli-server') {
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '';

    if (preg_match('/\.(?:js|ico|gif|jpg|png|css|asc|txt|svg)$/', $path)) {
        // Serves customization.
        if (preg_match('/^\/user\.(js|css)$/', $path)) {
            return false;
        }

        // Serves frontend.
        if (preg_match('/^\/public/', $path)) {
            return false;
        }

        // Serves favicons and thumbnails.
        if (preg_match('/^\/data/', $path)) {
            return false;
        }

        // The rewrite rules do not match real servers perfectly –
        // Apache would use an internal redirect instead of HTTP one.

        // Redirects to proper location for images.
        if (preg_match('/\/(favicons|thumbnails)/', $path)) {
            header('Location: /data' . $path);
            exit;
        }

        // Redirects to proper location for frontend.
        header('Location: /public' . $path);
        exit;
    } else {
        // taken from cli arg, hack for updater
        $_SERVER['SERVER_ADDR'] = $_SERVER['SERVER_NAME'];
        require 'index.php';
    }
}
