<?php

// SPDX-FileCopyrightText: 2015 Kevin P <wazari972@gmail.com>
// SPDX-FileCopyrightText: 2016–2023 Jan Tojnar <jtojnar@gmail.com>
// SPDX-License-Identifier: GPL-3.0-or-later

declare(strict_types=1);

namespace daos;

use helpers\Authentication;

/**
 * Proxy for accessing tag colors.
 */
final class Tags implements TagsInterface {
    public function __construct(
        private Authentication $authentication,
        /** Instance of backend-specific Tags class */
        private TagsInterface $backend
    ) {
    }

    public function saveTagColor(string $tag, string $color): void {
        $this->backend->saveTagColor($tag, $color);
    }

    public function autocolorTag(string $tag): void {
        $this->backend->autocolorTag($tag);
    }

    public function get(): array {
        $tags = $this->backend->get();
        // remove items with private tags
        if (!$this->authentication->showPrivateTags()) {
            foreach ($tags as $idx => $tag) {
                if (str_starts_with($tag['tag'], '@')) {
                    unset($tags[$idx]);
                }
            }
            $tags = array_values($tags);
        }

        return $tags;
    }

    public function getWithUnread(): array {
        return $this->backend->getWithUnread();
    }

    public function cleanup(array $tags): void {
        $this->backend->cleanup($tags);
    }

    public function hasTag(string $tag): bool {
        return $this->backend->hasTag($tag);
    }

    public function delete(string $tag): void {
        $this->backend->delete($tag);
    }
}
