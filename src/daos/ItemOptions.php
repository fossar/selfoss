<?php

declare(strict_types=1);

namespace daos;

use DateTime;

/**
 * Object holding parameters for querying items.
 */
final class ItemOptions {
    /** @readonly */
    public ?int $offset;

    /** @readonly */
    public ?string $search;

    /**
     * Maximum number of items to fetch from the database (unbounded)
     *
     * @readonly
     */
    public ?int $pageSize;

    /** @readonly */
    public ?DateTime $fromDatetime;

    /** @readonly */
    public ?int $fromId;

    /** @readonly */
    public ?DateTime $updatedSince;

    /** @readonly */
    public ?string $tag;

    /**
     * @var 'starred'|'unread'|null
     *
     * @readonly
     */
    public ?string $filter;

    /** @readonly */
    public ?int $source;

    /**
     * @var int[]
     *
     * @readonly
     */
    public array $extraIds;

    /**
     * Creates new ItemOptions object ensuring the values are proper types.
     *
     * @param array<string, mixed> $data
     */
    public function __construct(array $data) {
        if (isset($data['offset']) && is_numeric($data['offset'])) {
            $this->offset = (int) $data['offset'];
        } else {
            $this->offset = 0;
        }

        if (isset($data['search']) && is_string($data['search']) && strlen($search = trim($data['search'])) > 0) {
            $this->search = $search;
        } else {
            $this->search = null;
        }

        if (isset($data['items']) && is_numeric($data['items'])) {
            $this->pageSize = (int) $data['items'];
        } else {
            $this->pageSize = null;
        }

        if (isset($data['fromDatetime']) && is_string($data['fromDatetime']) && strlen($data['fromDatetime']) > 0) {
            $this->fromDatetime = new DateTime($data['fromDatetime']);
        } else {
            $this->fromDatetime = null;
        }

        if (isset($data['fromId']) && is_numeric($data['fromId'])) {
            $this->fromId = (int) $data['fromId'];
        } else {
            $this->fromId = null;
        }

        if (isset($data['updatedsince']) && is_string($data['updatedsince']) && strlen($data['updatedsince']) > 0) {
            $this->updatedSince = new DateTime($data['updatedsince']);
        } else {
            $this->updatedSince = null;
        }

        if (isset($data['tag']) && is_string($data['tag']) && strlen($tag = trim($data['tag'])) > 0) {
            $this->tag = $tag;
        } else {
            $this->tag = null;
        }

        if (isset($data['type']) && is_string($data['type']) && in_array($filter = trim($data['type']), ['starred', 'unread'], true)) {
            $this->filter = $filter;
        } else {
            $this->filter = null;
        }

        if (isset($data['source']) && is_numeric($data['source'])) {
            $this->source = (int) $data['source'];
        } else {
            $this->source = null;
        }

        if (isset($data['extraIds']) && is_array($data['extraIds'])) {
            $this->extraIds = array_map(
                fn($val) => (int) $val,
                $data['extraIds']
            );
        } else {
            $this->extraIds = [];
        }
    }
}
