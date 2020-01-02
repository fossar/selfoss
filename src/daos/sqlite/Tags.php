<?php

namespace daos\sqlite;

/**
 * Class for accessing persistent saved sources -- mysql
 *
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
class Tags extends \daos\mysql\Tags {
    /** @var class-string SQL helper */
    protected static $stmt = Statements::class;
}
