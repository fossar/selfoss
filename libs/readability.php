<?php

/**
 * External lib for readability.com access
 *
 * @package    controllers
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @author     oxman @github
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
function readability($key, $link) {
    $content = file_get_contents("https://readability.com/api/content/v1/parser?token=" . $key . "&url=" . $link);
    $data = json_decode($content);
    return $data->content;
}
