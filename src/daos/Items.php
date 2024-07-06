<?php

declare(strict_types=1);

namespace daos;

use DateTime;
use DateTimeImmutable;
use helpers\Authentication;

/**
 * Class for accessing persistent saved items
 *
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Harald Lapp <harald.lapp@gmail.com>
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
class Items implements ItemsInterface {
    private Authentication $authentication;
    private ItemsInterface $backend;

    public function __construct(
        Authentication $authentication,
        ItemsInterface $backend
    ) {
        $this->authentication = $authentication;
        $this->backend = $backend;
    }

    public function mark(array $ids): void {
        $this->backend->mark($ids);
    }

    public function unmark(array $ids): void {
        $this->backend->unmark($ids);
    }

    public function starr(int $id): void {
        $this->backend->starr($id);
    }

    public function unstarr(int $id): void {
        $this->backend->unstarr($id);
    }

    public function add(array $values): void {
        $this->backend->add($values);
    }

    public function exists(string $uid): bool {
        return $this->backend->exists($uid);
    }

    public function findAll(array $itemsInFeed, int $sourceId): array {
        return $this->backend->findAll($itemsInFeed, $sourceId);
    }

    public function updateLastSeen(array $itemIds): void {
        $this->backend->updateLastSeen($itemIds);
    }

    public function cleanup(?DateTime $minDate): void {
        $this->backend->cleanup($minDate);
    }

    /**
     * returns items
     *
     * @param ItemOptions $options search, offset and filter params
     *
     * @return array<array{id: int, datetime: DateTime, title: string, content: string, unread: bool, starred: bool, source: int, thumbnail: string, icon: string, uid: string, link: string, updatetime: DateTime, author: string, sourcetitle: string, tags: string[]}> items as array
     */
    public function get(ItemOptions $options): array {
        $items = $this->backend->get($options);

        // remove private posts with private tags
        if (!$this->authentication->showPrivateTags()) {
            foreach ($items as $idx => $item) {
                foreach ($item['tags'] as $tag) {
                    if (str_starts_with(trim($tag), '@')) {
                        unset($items[$idx]);
                        break;
                    }
                }
            }
            $items = array_values($items);
        }

        // remove posts with hidden tags
        if ($options->tag !== null) {
            foreach ($items as $idx => $item) {
                foreach ($item['tags'] as $tag) {
                    if (str_starts_with(trim($tag), '#')) {
                        unset($items[$idx]);
                        break;
                    }
                }
            }
            $items = array_values($items);
        }

        return $items;
    }

    public function hasMore(): bool {
        return $this->backend->hasMore();
    }

    public function sync(int $sinceId, DateTime $notBefore, DateTime $since, int $howMany): array {
        return $this->backend->sync($sinceId, $notBefore, $since, $howMany);
    }

    public function lowestIdOfInterest(): int {
        return $this->backend->lowestIdOfInterest();
    }

    public function lastId(): int {
        return $this->backend->lastId();
    }

    public function hasThumbnail(string $thumbnail): bool {
        return $this->backend->hasThumbnail($thumbnail);
    }

    public function hasIcon(string $icon): bool {
        return $this->backend->hasIcon($icon);
    }

    public function numberOfUnread(): int {
        return $this->backend->numberOfUnread();
    }

    public function stats(): array {
        return $this->backend->stats();
    }

    public function lastUpdate(): ?DateTimeImmutable {
        return $this->backend->lastUpdate();
    }

    public function statuses(DateTime $since): array {
        return $this->backend->statuses($since);
    }

    public function bulkStatusUpdate(array $statuses): void {
        $this->backend->bulkStatusUpdate($statuses);
    }
}
