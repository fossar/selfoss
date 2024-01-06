<?php

declare(strict_types=1);

namespace daos;

use DateTime;

/**
 * Object holding parameters for querying items.
 */
final class ItemOptions {
    /** @readonly */
    public ?int $offset = 0;

    /** @readonly */
    public ?string $search = null;

    /**
     * Maximum number of items to fetch from the database (unbounded)
     *
     * @readonly
     */
    public ?int $pageSize = null;

    /** @readonly */
    public ?DateTime $fromDatetime = null;

    /** @readonly */
    public ?int $fromId = null;

    /** @readonly */
    public ?DateTime $updatedSince = null;

    /** @readonly */
    public ?string $tag = null;

    /**
     * @var 'starred'|'unread'|null
     *
     * @readonly
     */
    public ?string $filter = null;

    /** @readonly */
    public ?int $source = null;

    /** @var int[] @readonly */
    public array $extraIds = [];

    /**
     * Creates new ItemOptions object ensuring the values are proper types.
     *
     * @param array<string, mixed> $data
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
            $options->fromDatetime = new DateTime($data['fromDatetime']);
        }

        if (isset($data['fromId']) && is_numeric($data['fromId'])) {
            $options->fromId = (int) $data['fromId'];
        }

        if (isset($data['updatedsince']) && is_string($data['updatedsince']) && strlen($data['updatedsince']) > 0) {
            $options->updatedSince = new DateTime($data['updatedsince']);
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
            $options->extraIds = array_map(
                fn($val) => (int) $val,
                $data['extraIds']
            );
        }

        return $options;
    }
}
