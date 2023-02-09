<?php

declare(strict_types=1);

namespace daos\sqlite;

/**
 * Class for accessing persistent saved items -- sqlite
 *
 * @copyright  Copyright (c) Harald Lapp <harald.lapp@gmail.com>
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Harald Lapp <harald.lapp@gmail.com>
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
class Items extends \daos\mysql\Items {
    /** @var class-string SQL helper */
    protected static $stmt = Statements::class;
}
