<?php

declare(strict_types=1);

namespace controllers\Opml;

use helpers\Authentication;
use helpers\View;
use Monolog\Logger;
use SimpleXMLElement;

/**
 * OPML loading and exporting controller
 *
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Michael Moore <stuporglue@gmail.com>
 * @author     Sean Rand <asanernd@gmail.com>
 */
class Import {
    /** @var array<string, array{id: int, tags: string[]}> Sources that have been imported from the OPML file */
    private array $imported = [];

    private Authentication $authentication;
    private Logger $logger;
    private \daos\Sources $sourcesDao;
    private \daos\Tags $tagsDao;
    private View $view;

    public function __construct(Authentication $authentication, Logger $logger, \daos\Sources $sourcesDao, \daos\Tags $tagsDao, View $view) {
        $this->authentication = $authentication;
        $this->logger = $logger;
        $this->sourcesDao = $sourcesDao;
        $this->tagsDao = $tagsDao;
        $this->view = $view;
    }

    /**
     * Add an OPML to the user's subscriptions
     * html
     *
     * @note Borrows from controllers/Sources.php:write
     */
    public function add(): void {
        $this->authentication->ensureIsPrivileged();

        http_response_code(400);

        /** @var string[] */
        $messages = [];

        try {
            if (!isset($_FILES['opml']) || ($opml = $_FILES['opml'])['error'] === UPLOAD_ERR_NO_FILE) {
                throw new \Exception('No file uploaded!');
            }

            $this->logger->debug('start OPML import ');

            if (!function_exists('simplexml_load_file')) {
                throw new \Exception('Missing SimpleXML PHP extension. Please install/enable it as described on https://www.php.net/manual/en/simplexml.installation.php');
            }

            $subs = false;
            $previousUseErrors = libxml_use_internal_errors(true);
            try {
                $subs = simplexml_load_file($opml['tmp_name']);

                if ($subs === false) {
                    // When parsing fails, check MIME type supplied by browser since it is possible user supplied file of a wrong type.
                    if (!in_array($opml['type'], ['application/xml', 'text/xml', 'text/x-opml+xml', 'text/x-opml'], true)) {
                        throw new \Exception('Unsupported file type: ' . $opml['type']);
                    }

                    // If type is correct, check the error reported by parser.
                    $error = libxml_get_last_error();
                    $errorDetail = $error !== false ? ': ' . $error->message : '';

                    throw new \Exception('Unable to parse OPML file' . $errorDetail);
                }
            } finally {
                libxml_use_internal_errors($previousUseErrors);
            }
            $errors = $this->processGroup($subs->body);

            // cleanup tags
            $this->tagsDao->cleanup($this->sourcesDao->getAllTags());

            $this->logger->debug('finished OPML import ');

            // show errors
            if (count($errors) > 0) {
                http_response_code(202);
                $messages = array_merge($messages, $errors);
            } else { // On success bring them back to their subscription list
                http_response_code(200);
                $amount = count($this->imported);
                $messages[] = 'Success! ' . $amount . ' feed' . ($amount !== 1 ? 's have' : ' has') . ' been imported.';
            }
        } catch (\Throwable $e) {
            $messages[] = $e->getMessage();
        }

        $this->view->jsonSuccess([
            'messages' => $messages,
        ]);
    }

    /**
     * Process a group of outlines
     *
     * - Recursive
     * - We use non-rss outline’s text as tags
     * - Reads outline elements from both the default and selfoss namespace
     *
     * @param SimpleXMLElement $xml A XML element object with <outline> children
     * @param string[] $tags An array of tags for the current <outline>
     *
     * @return string[] titles of feeds that could not be added to subscriptions
     */
    private function processGroup(SimpleXMLElement $xml, array $tags = []): array {
        $errors = [];

        $xml->registerXPathNamespace('selfoss', 'https://selfoss.aditu.de/');

        // In Google Reader (and now Feedly), folders/tags/labels were just the text of the outline parent.
        // Now, it is not valid for an <outline> element with the default “text” type to use the “title” attribute
        // but both Google Reader and Feedly duplicate the “text” attribute as “title” so it seems to be common.
        // Feedly seems to prefer “title” for both category names and feed names.
        // We will do the same in case someone mistakenly exports the “title” and forgets about “text”.
        /** @var SimpleXMLElement attributes */
        $attrs = $xml->attributes();
        $title = (string) $attrs->title;
        $title = $title ?: (string) $attrs->text;
        if ($title !== '' && $title !== '/') {
            $tags[] = $title;
            // for new tags, try to import tag color, otherwise use random color
            if (!$this->tagsDao->hasTag($title)) {
                /** @var SimpleXMLElement attributes in selfoss namespace */
                $selfossAttrs = $xml->attributes('selfoss', true);
                $tagColor = (string) $selfossAttrs->color;
                if ($tagColor !== '') {
                    $this->tagsDao->saveTagColor($title, $tagColor);
                } else {
                    $this->tagsDao->autocolorTag($title);
                }
            }
        }

        // parse outline items from the default and selfoss namespaces
        foreach ($xml->xpath('outline|selfoss:outline') ?: [] as $outline) {
            if (count($outline->children()) + count($outline->children('selfoss', true)) > 0) {
                // outline element has children, recurse into it
                $ret = $this->processGroup($outline, $tags);
                $errors = array_merge($errors, $ret);
            } else {
                $ret = $this->addSubscription($outline, $tags);
                if ($ret !== true) {
                    $errors[] = $ret;
                }
            }
        }

        return $errors;
    }

    /**
     * Add new feed subscription
     *
     * @param SimpleXMLElement $xml An <outline> XML element corresponding to a feed
     * @param string[] $tags of the entry
     *
     * @return true|string true on success or item title on error
     */
    private function addSubscription(SimpleXMLElement $xml, array $tags) {
        // OPML Required attributes: text, xmlUrl, type
        // Optional attributes: title, htmlUrl, language, title, version
        // Selfoss namespaced attributes: spout, params

        /** @var SimpleXMLElement attributes */
        $attrs = $xml->attributes();
        /** @var SimpleXMLElement attributes in selfoss namespace */
        $nsattrs = $xml->attributes('selfoss', true);

        // description
        // Google Reader (and now Feedly) duplicate the feed title in “title” and “text” attributes.
        // Prefer “title” as it is optional and it might contain more detailed label.
        $title = (string) $attrs->title;
        if ($title === '') {
            $title = (string) $attrs->text;
        }

        // RSS URL
        $data = [
            'url' => (string) $attrs->xmlUrl,
        ];

        // set spout for new item
        if ($nsattrs->spout || $nsattrs->params) {
            if (!($nsattrs->spout && $nsattrs->params)) {
                $this->logger->warning("OPML import: failed to import '$title'");
                $missingAttr = $nsattrs->spout ? '"selfoss:params"' : '"selfoss:spout"';
                $this->logger->debug("Missing attribute: $missingAttr");

                return $title;
            }
            $spout = (string) $nsattrs->spout;
            $data = json_decode(html_entity_decode((string) $nsattrs->params), true);
        } elseif (in_array((string) $attrs->type, ['rss', 'atom'], true)) {
            $spout = 'spouts\rss\feed';
        } else {
            $this->logger->warning("OPML import: failed to import '$title'");
            $this->logger->debug("Invalid type '$attrs->type': only 'rss' and 'atom' are supported");

            return $title;
        }

        // validate new item
        $validation = @$this->sourcesDao->validate($title, $spout, $data);
        if ($validation !== true) {
            $this->logger->warning("OPML import: failed to import '$title'");
            $this->logger->debug('Invalid source', $validation);

            return $title;
        }

        // insert item or update tags for already imported item
        $hash = md5($title . $spout . json_encode($data));
        if (array_key_exists($hash, $this->imported)) {
            $this->imported[$hash]['tags'] = array_unique(array_merge($this->imported[$hash]['tags'], $tags));
            $tags = $this->imported[$hash]['tags'];
            $this->sourcesDao->edit($this->imported[$hash]['id'], $title, $tags, '', $spout, $data);
            $this->logger->debug("OPML import: updated tags for '$title'");
        } elseif ($id = $this->sourcesDao->checkIfExists($title, $spout, $data)) {
            $tags = array_unique(array_merge($this->sourcesDao->getTags($id), $tags));
            $this->sourcesDao->edit($id, $title, $tags, '', $spout, $data);
            $this->imported[$hash] = ['id' => $id, 'tags' => $tags];
            $this->logger->debug("OPML import: updated tags for '$title'");
        } else {
            $id = $this->sourcesDao->add($title, $tags, '', $spout, $data);
            $this->imported[$hash] = ['id' => $id, 'tags' => $tags];
            $this->logger->debug("OPML import: successfully imported '$title'");
        }

        // success
        return true;
    }
}
