<?php

namespace controllers\Opml;

use helpers\Authentication;
use helpers\Configuration;
use helpers\SpoutLoader;
use Monolog\Logger;

/**
 * OPML loading and exporting controller
 *
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Michael Moore <stuporglue@gmail.com>
 * @author     Sean Rand <asanernd@gmail.com>
 */
class Export {
    /** @var Authentication authentication helper */
    private $authentication;

    /** @var Configuration configuration */
    private $configuration;

    /** @var Logger */
    private $logger;

    /** @var SpoutLoader */
    private $spoutLoader;

    /** @var \XMLWriter */
    private $writer;

    /** @var \daos\Sources */
    private $sourcesDao;

    /** @var \daos\Tags */
    private $tagsDao;

    public function __construct(Authentication $authentication, Configuration $configuration, Logger $logger, \daos\Sources $sourcesDao, SpoutLoader $spoutLoader, \daos\Tags $tagsDao, \XMLWriter $writer) {
        $this->authentication = $authentication;
        $this->configuration = $configuration;
        $this->logger = $logger;
        $this->sourcesDao = $sourcesDao;
        $this->spoutLoader = $spoutLoader;
        $this->tagsDao = $tagsDao;
        $this->writer = $writer;
    }

    /**
     * Generate an OPML outline element from a source
     *
     * @note Uses the selfoss namespace to store information about spouts
     *
     * @param array $source source
     *
     * @return void
     */
    private function writeSource(array $source) {
        // retrieve the feed url of the source
        $params = json_decode(html_entity_decode($source['params']), true);
        $feedUrl = $this->spoutLoader->get($source['spout'])->getXmlUrl($params);

        // if the spout doesn't return a feed url, the source isn't an RSS feed
        if ($feedUrl !== null) {
            $this->writer->startElement('outline');
        } else {
            $this->writer->startElementNS('selfoss', 'outline', null);
        }

        $this->writer->writeAttribute('title', $source['title']);
        $this->writer->writeAttribute('text', $source['title']);

        if ($feedUrl !== null) {
            $this->writer->writeAttribute('xmlUrl', $feedUrl);
            $this->writer->writeAttribute('type', 'rss');
        }

        // write spout name and parameters in namespaced attributes
        $this->writer->writeAttributeNS('selfoss', 'spout', null, $source['spout']);
        $this->writer->writeAttributeNS('selfoss', 'params', null, html_entity_decode($source['params']));

        $this->writer->endElement();  // outline
        $this->logger->debug('done exporting source ' . $source['title']);
    }

    /**
     * Export user's subscriptions to OPML file
     *
     * @note Uses the selfoss namespace to store selfoss-specific information
     *
     * @return void
     */
    public function export() {
        $this->authentication->needsLoggedIn();

        // save content as file and suggest file name
        $exportName = 'selfoss-subscriptions-' . date('YmdHis') . '.xml';
        header('Content-Disposition: attachment; filename="' . $exportName . '"');
        header('Content-Type: text/xml; charset=UTF-8');

        $this->logger->debug('start OPML export');
        $this->writer->openMemory();
        $this->writer->setIndent(true);
        $this->writer->setIndentString('    ');

        $this->writer->startDocument('1.0', 'UTF-8');

        $this->writer->startElement('opml');
        $this->writer->writeAttribute('version', '2.0');
        $this->writer->writeAttribute('xmlns:selfoss', 'https://selfoss.aditu.de/');

        // selfoss version, XML format version and creation date
        $this->writer->startElementNS('selfoss', 'meta', null);
        $this->writer->writeAttribute('generatedBy', 'selfoss-' . SELFOSS_VERSION);
        $this->writer->writeAttribute('version', '1.0');
        $this->writer->writeAttribute('createdOn', date('r'));
        $this->writer->endElement();  // meta
        $this->logger->debug('OPML export: finished writing meta');

        $this->writer->startElement('head');
        $user = $this->configuration->username;
        $this->writer->writeElement('title', ($user ? $user . '\'s' : 'My') . ' subscriptions in selfoss');
        $this->writer->endElement();  // head
        $this->logger->debug('OPML export: finished writing head');

        $this->writer->startElement('body');

        // create tree structure for tagged and untagged sources
        $sources = ['tagged' => [], 'untagged' => []];
        foreach ($this->sourcesDao->get() as $source) {
            if ($source['tags']) {
                foreach ($source['tags'] as $tag) {
                    $sources['tagged'][$tag][] = $source;
                }
            } else {
                $sources['untagged'][] = $source;
            }
        }

        // create associative array with tag names as keys, colors as values
        $tagColors = [];
        foreach ($this->tagsDao->get() as $key => $tag) {
            $this->logger->debug('OPML export: tag ' . $tag['tag'] . ' has color ' . $tag['color']);
            $tagColors[$tag['tag']] = $tag['color'];
        }

        // generate outline elements for all sources
        foreach ($sources['tagged'] as $tag => $children) {
            $this->logger->debug("OPML export: exporting tag $tag sources");
            $this->writer->startElement('outline');
            $this->writer->writeAttribute('title', $tag);
            $this->writer->writeAttribute('text', $tag);

            $this->writer->writeAttributeNS('selfoss', 'color', null, $tagColors[$tag]);

            foreach ($children as $source) {
                $this->writeSource($source);
            }

            $this->writer->endElement();  // outline
        }

        $this->logger->debug('OPML export: exporting untagged sources');
        foreach ($sources['untagged'] as $key => $source) {
            $this->writeSource($source);
        }

        $this->writer->endElement();  // body

        $this->writer->endDocument();
        $this->logger->debug('finished OPML export');

        echo $this->writer->outputMemory();
    }
}
