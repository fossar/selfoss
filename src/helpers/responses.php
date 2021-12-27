<?php

namespace helpers;

/**
 * send error message
 *
 * @param string $message
 *
 * @return void
 */
function sendError($message) {
    header('HTTP/1.0 400 Bad Request');
    header('Content-type: text/plain');
    exit($message);
}
