<?php

// SPDX-FileCopyrightText: © 2022 Jan Tojnar
// SPDX-License-Identifier: GPL-3.0-or-later

namespace helpers\RssBridge;

use Nette\Caching\Cache;
use RssBridge;

class CacheAdapter implements RssBridge\CacheInterface {
    /** @var array<string, Cache> */
    private array $subCaches = [];

    private Cache $currentCache;
    private mixed $currentKey;

    public function __construct(
        private Cache $cache
    ) {
        $this->currentCache = $this->cache;
    }

    /**
     * {@inheritdoc}
     */
    public function setScope($scope): self {
        if ($scope === '') {
            $this->currentCache = $this->cache;
        } elseif (!isset($subCaches[$scope])) {
            $subCaches[$scope] = $this->cache->derive($scope);
            $this->currentCache = $subCaches[$scope];
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setKey($key): self {
        $this->currentKey = $key;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function loadData(): mixed {
        return $this->currentCache->load($this->currentKey);
    }

    /**
     * {@inheritdoc}
     */
    public function saveData($data): self {
        $this->currentCache->save($this->currentKey, $data);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getTime(): int {
        // TODO: Nette’s cache is expiration based and modification time is internal.
        // Consider making RSS-Bridge work that way too.
        return time();
    }

    /**
     * {@inheritdoc}
     */
    public function purgeCache($seconds): void {
        // Nette takes care of expiration for us.
    }
}
