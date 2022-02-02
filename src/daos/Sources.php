<?php

namespace daos;

use helpers\Authentication;
use helpers\Configuration;
use helpers\SpoutLoader;
use Monolog\Logger;

/**
 * Class for accessing persistent saved sources
 *
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Harald Lapp <harald.lapp@gmail.com>
 * @author     Daniel Seither <post@tiwoc.de>
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 *
 * @mixin SourcesInterface
 */
class Sources {
    /** @var SourcesInterface Instance of backend specific sources class */
    private $backend;

    /** @var Authentication authentication helper */
    private $authentication;

    /** @var Configuration configuration */
    private $configuration;

    /** @var Logger */
    private $logger;

    /** @var SpoutLoader spout loader */
    private $spoutLoader;

    /**
     * Constructor.
     *
     * @return void
     */
    public function __construct(Authentication $authentication, Configuration $configuration, Logger $logger, SourcesInterface $backend, SpoutLoader $spoutLoader) {
        $this->authentication = $authentication;
        $this->configuration = $configuration;
        $this->backend = $backend;
        $this->logger = $logger;
        $this->spoutLoader = $spoutLoader;
    }

    /**
     * pass any method call to the backend.
     *
     * @param string $name name of the function
     * @param array $args arguments
     *
     * @return mixed methods return value
     */
    public function __call($name, $args) {
        if (method_exists($this->backend, $name)) {
            return call_user_func_array([$this->backend, $name], $args);
        } else {
            $this->logger->error('Unimplemented method for ' . $this->configuration->dbType . ': ' . $name);
        }
    }

    /**
     * @param int|null $id
     */
    public function get($id = null) {
        $sources = $this->backend->get($id);
        if ($id === null) {
            // remove items with private tags
            if (!$this->authentication->showPrivateTags()) {
                foreach ($sources as $idx => $source) {
                    foreach ($source['tags'] as $tag) {
                        if (strpos(trim($tag), '@') === 0) {
                            unset($sources[$idx]);
                            break;
                        }
                    }
                }
                $sources = array_values($sources);
            }
        }

        return $sources;
    }

    /**
     * validate new data for a given source
     *
     * @param string $title title of the source
     * @param string $spout class path for the spout
     * @param array $params parameters supplied to the spout
     *
     * @return array<string,string>|true true on success or array of errors on failure
     *
     * @author Tobias Zeising
     */
    public function validate($title, $spout, array $params) {
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
                if (!is_array($validation)) {
                    $validation = [$validation];
                }

                foreach ($validation as $validate) {
                    if ($validate === 'alpha' && !preg_match("([A-Za-z._\b]+)", $value)) {
                        $result[$id] = 'only alphabetic characters allowed for ' . $spout->params[$id]['title'];
                    } elseif ($validate === 'email' && !preg_match('(^[^0-9][a-zA-Z0-9_]+([.][a-zA-Z0-9_]+)*[@][a-zA-Z0-9_]+([.][a-zA-Z0-9_]+)*[.][a-zA-Z]{2,4}$)', $value)) {
                        $result[$id] = $spout->params[$id]['title'] . ' is not a valid email address';
                    } elseif ($validate === 'numeric' && !is_numeric($value)) {
                        $result[$id] = 'only numeric values allowed for ' . $spout->params[$id]['title'];
                    } elseif ($validate === 'int' && (int) $value != $value) {
                        $result[$id] = 'only integer values allowed for ' . $spout->params[$id]['title'];
                    } elseif ($validate === 'alnum' && !preg_match("([A-Za-z0-9._\b]+)", $value)) {
                        $result[$id] = 'only alphanumeric values allowed for ' . $spout->params[$id]['title'];
                    } elseif ($validate === 'notempty' && strlen(trim($value)) === 0) {
                        $result[$id] = 'empty value for ' . $spout->params[$id]['title'] . ' not allowed';
                    }
                }
            }

            // select: user sent value which is not a predefined option?
            foreach ($params as $id => $value) {
                if ($spout->params[$id]['type'] !== 'select') {
                    continue;
                }

                $values = $spout->params[$id]['values'];

                $found = false;
                foreach ($values as $optionName => $optionTitle) {
                    if ($optionName == $value) {
                        $found = true;
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
