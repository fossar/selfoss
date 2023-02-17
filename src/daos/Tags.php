<?php

declare(strict_types=1);

namespace daos;

use helpers\Authentication;
use helpers\Configuration;
use Monolog\Logger;

/**
 * Class for accessing tag colors
 *
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 *
 * @mixin TagsInterface
 */
class Tags {
    /** @var TagsInterface Instance of backend specific sources class */
    private $backend;

    /** @var Authentication authentication helper */
    private $authentication;

    /** @var Configuration configuration */
    private $configuration;

    /** @var Logger */
    private $logger;

    public function __construct(
        Authentication $authentication,
        Configuration $configuration,
        Logger $logger,
        TagsInterface $backend
    ) {
        $this->authentication = $authentication;
        $this->backend = $backend;
        $this->configuration = $configuration;
        $this->logger = $logger;
    }

    /**
     * Returns all tags user has access to.
     *
     * @return array<array{tag: string, color: string}>
     */
    public function get(): array {
        $tags = $this->backend->get();
        // remove items with private tags
        if (!$this->authentication->showPrivateTags()) {
            foreach ($tags as $idx => $tag) {
                if (strpos($tag['tag'], '@') === 0) {
                    unset($tags[$idx]);
                }
            }
            $tags = array_values($tags);
        }

        return $tags;
    }

    /**
     * pass any method call to the backend.
     *
     * @param string $name name of the function
     * @param array $args arguments
     *
     * @return mixed methods return value
     */
    public function __call(string $name, array $args) {
        if (method_exists($this->backend, $name)) {
            return call_user_func_array([$this->backend, $name], $args);
        } else {
            $this->logger->error('Unimplemented method for ' . $this->configuration->dbType . ': ' . $name);
        }
    }
}
