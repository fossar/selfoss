<?php

declare(strict_types=1);

namespace daos;

use helpers\Authentication;
use helpers\SpoutLoader;
use spouts\Parameter;

/**
 * Class for accessing persistent saved sources
 *
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Harald Lapp <harald.lapp@gmail.com>
 * @author     Daniel Seither <post@tiwoc.de>
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
class Sources implements SourcesInterface {
    private Authentication $authentication;
    private SourcesInterface $backend;
    private SpoutLoader $spoutLoader;

    public function __construct(
        Authentication $authentication,
        SourcesInterface $backend,
        SpoutLoader $spoutLoader
    ) {
        $this->authentication = $authentication;
        $this->backend = $backend;
        $this->spoutLoader = $spoutLoader;
    }

    public function add(string $title, array $tags, ?string $filter, string $spout, array $params): int {
        return $this->backend->add($title, $tags, $filter, $spout, $params);
    }

    public function edit(int $id, string $title, array $tags, ?string $filter, string $spout, array $params): void {
        $this->backend->edit($id, $title, $tags, $filter, $spout, $params);
    }

    public function delete(int $id): void {
        $this->backend->delete($id);
    }

    public function error(int $id, string $error): void {
        $this->backend->error($id, $error);
    }

    public function saveLastUpdate(int $id, ?int $lastEntry): void {
        $this->backend->saveLastUpdate($id, $lastEntry);
    }

    public function count(): int {
        return $this->backend->count();
    }

    public function getByLastUpdate(): array {
        return $this->backend->getByLastUpdate();
    }

    public function get(int $id): ?array {
        return $this->backend->get($id);
    }

    public function getAll(): array {
        $sources = $this->backend->getAll();

        // remove items with private tags
        if (!$this->authentication->showPrivateTags()) {
            foreach ($sources as $idx => $source) {
                foreach ($source['tags'] as $tag) {
                    if (str_starts_with(trim($tag), '@')) {
                        unset($sources[$idx]);
                        break;
                    }
                }
            }
            $sources = array_values($sources);
        }

        return $sources;
    }

    public function getWithUnread(): array {
        return $this->backend->getWithUnread();
    }

    public function getWithIcon(): array {
        return $this->backend->getWithIcon();
    }

    public function getAllTags(): array {
        return $this->backend->getAllTags();
    }

    public function getTags(int $id): array {
        return $this->backend->getTags($id);
    }

    public function checkIfExists(string $title, string $spout, array $params): int {
        return $this->backend->checkIfExists($title, $spout, $params);
    }

    /**
     * validate new data for a given source
     *
     * @param string $title title of the source
     * @param string $spout class path for the spout
     * @param array<string, mixed> $params parameters supplied to the spout
     *
     * @return array<string,string>|true true on success or array of errors on failure
     *
     * @author Tobias Zeising
     */
    public function validate(string $title, string $spout, array $params) {
        $result = [];

        // title
        if (strlen(trim($title)) === 0) {
            $result['title'] = 'no text for title given';
        }

        // spout type
        $spout = $this->spoutLoader->get($spout);
        if ($spout === null) {
            $result['spout'] = 'invalid spout type';
        } else { // check params
            // required but not given params
            foreach ($spout->params as $id => $param) {
                if ($param['required'] === false) {
                    continue;
                }
                $found = false;
                foreach ($params as $userParamId => $userParamValue) {
                    if ($userParamId == $id) {
                        $found = true;
                    }
                }
                if ($found === false) {
                    $result[$id] = 'param ' . $param['title'] . ' required but not given';
                }
            }

            // given params valid?
            foreach ($params as $id => $value) {
                if (!isset($spout->params[$id])) {
                    $result[$id] = 'unexpected param ' . $id;

                    return $result;
                }

                $validation = $spout->params[$id]['validation'];
                // @phpstan-ignore-next-line function.alreadyNarrowedType (User can create their own spouts so we cannot necessarily trust PHPDoc.)
                if (!is_array($validation)) {
                    $validation = [$validation];
                }

                foreach ($validation as $validate) {
                    if ($validate === Parameter::VALIDATION_ALPHA && !preg_match("([A-Za-z._\b]+)", $value)) {
                        $result[$id] = 'only alphabetic characters allowed for ' . $spout->params[$id]['title'];
                    } elseif ($validate === Parameter::VALIDATION_EMAIL && !preg_match('(^[^0-9][a-zA-Z0-9_]+([.][a-zA-Z0-9_]+)*[@][a-zA-Z0-9_]+([.][a-zA-Z0-9_]+)*[.][a-zA-Z]{2,4}$)', $value)) {
                        $result[$id] = $spout->params[$id]['title'] . ' is not a valid email address';
                    } elseif ($validate === Parameter::VALIDATION_NUMERIC && !is_numeric($value)) {
                        $result[$id] = 'only numeric values allowed for ' . $spout->params[$id]['title'];
                    } elseif ($validate === Parameter::VALIDATION_INT && (int) $value != $value) {
                        $result[$id] = 'only integer values allowed for ' . $spout->params[$id]['title'];
                    } elseif ($validate === Parameter::VALIDATION_ALPHANUMERIC && !preg_match("([A-Za-z0-9._\b]+)", $value)) {
                        $result[$id] = 'only alphanumeric values allowed for ' . $spout->params[$id]['title'];
                    } elseif ($validate === Parameter::VALIDATION_NONEMPTY && strlen(trim($value)) === 0) {
                        $result[$id] = 'empty value for ' . $spout->params[$id]['title'] . ' not allowed';
                    }
                }
            }

            // select: user sent value which is not a predefined option?
            foreach ($params as $id => $value) {
                if ($spout->params[$id]['type'] !== Parameter::TYPE_SELECT) {
                    continue;
                }

                $values = $spout->params[$id]['values'] ?? [];

                $found = false;
                foreach ($values as $optionName => $optionTitle) {
                    if ($optionName == $value) {
                        $found = true;
                        break;
                    }
                }
                if ($found === false) {
                    $result[$id] = 'param ' . $spout->params[$id]['title'] . ' was not set to a predefined value';
                }
            }
        }

        if (count($result) > 0) {
            return $result;
        }

        return true;
    }
}
