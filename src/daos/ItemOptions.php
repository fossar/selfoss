<?php

declare(strict_types=1);

namespace daos;

use DateTime;

/**
 * Object holding parameters for querying items.
 */
final class ItemOptions {
    /** @var ?int @readonly */
    public $offset = 0;

    /** @var ?string @readonly */
    public $search = null;

    /** @var ?int maximum number of items to fetch from the database (unbounded) @readonly */
    public $pageSize = null;

    /** @var ?DateTime @readonly */
    public $fromDatetime = null;

    /** @var ?int @readonly */
    public $fromId = null;

    /** @var ?DateTime @readonly */
    public $updatedSince = null;

    /** @var ?string @readonly */
    public $tag = null;

    /** @var 'starred'|'unread'|null @readonly */
    public $filter = null;

    /** @var ?int @readonly */
    public $source = null;

    /** @var int[] @readonly */
    public $extraIds = [];

    /**
     * Creates new ItemOptions object ensuring the values are proper types.
     *
     * @return static
     */
    public static function fromUser(array $data): self {
        $options = new static();

        if (isset($data['offset']) && is_numeric($data['offset'])) {
            $options->offset = (int) $data['offset'];
        }

        if (isset($data['search']) && is_string($data['search']) && strlen($search = trim($data['search'])) > 0) {
            $options->search = $search;
        }

        if (isset($data['items']) && is_numeric($data['items'])) {
            $options->pageSize = (int) $data['items'];
        }

        if (isset($data['fromDatetime']) && is_string($data['fromDatetime']) && strlen($data['fromDatetime']) > 0) {
            $options->fromDatetime = new \DateTime($data['fromDatetime']);
        }

        if (isset($data['fromId']) && is_numeric($data['fromId'])) {
            $options->fromId = (int) $data['fromId'];
        }

        if (isset($data['updatedsince']) && is_string($data['updatedsince']) && strlen($data['updatedsince']) > 0) {
            $options->updatedSince = new \DateTime($data['updatedsince']);
        }

        if (isset($data['tag']) && is_string($data['tag']) && strlen($tag = trim($data['tag'])) > 0) {
            $options->tag = $tag;
        }

        if (isset($data['type']) && is_string($data['type']) && in_array($filter = trim($data['type']), ['starred', 'unread'], true)) {
            $options->filter = $filter;
        }

        if (isset($data['source']) && is_numeric($data['source'])) {
            $options->source = (int) $data['source'];
        }

        if (isset($data['extraIds']) && is_array($data['extraIds'])) {
            $options->extraIds = array_map(function($val) {
                return (int) $val;
            }, $data['extraIds']);
        }

        return $options;
    }
}
