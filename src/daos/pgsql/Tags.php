<?php

namespace daos\pgsql;

/**
 * Class for accessing persistant saved tags -- postgresql
 *
 * @copyright   Copyright (c) Michael Jackson <michael.o.jackson@gmail.com>
 * @license     GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author      Michael Jackson <michael.o.jackson@gmail.com>
 * @author      Tobias Zeising <tobias.zeising@aditu.de>
 */
class Tags extends \daos\mysql\Tags {
    /** @var class-string SQL helper */
    protected static $stmt = Statements::class;
}
