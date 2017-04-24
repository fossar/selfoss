<?php

// Only run this if running from php 5.4 embedded server
if (PHP_SAPI === 'cli-server') {
    if (preg_match('/\.(?:js|ico|gif|jpg|png|css|asc|txt|svg)(?:\?.*)?$/', $_SERVER['REQUEST_URI'])) {
        // serves fronted
        if (preg_match('/^\/public/', $_SERVER['REQUEST_URI'])) {
            return false;
        }

        // serves favicons
        if (preg_match('/^\/data/', $_SERVER['REQUEST_URI'])) {
            return false;
        }

        //redirects to proper location for favicons
        if (preg_match('/(favicons|thumbnails)/', $_SERVER['REQUEST_URI'])) {
            header('Location: /data' . $_SERVER['REQUEST_URI']);
            exit;
        }

        //redirects to proper location for frontend
        header('Location: /public' . $_SERVER['REQUEST_URI']);
        exit;
    } else {
        // taken from cli arg, hack for updater
        $_SERVER['SERVER_ADDR'] = $_SERVER['SERVER_NAME'];
        require 'index.php';
    }
}
