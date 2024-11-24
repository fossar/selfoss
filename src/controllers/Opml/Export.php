<?php

declare(strict_types=1);

namespace controllers\Opml;

use helpers\Authentication;
use helpers\Configuration;
use helpers\SpoutLoader;
use helpers\StringKeyedArray;
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
    private Authentication $authentication;
    private Configuration $configuration;
    private Logger $logger;
    private SpoutLoader $spoutLoader;
    private \XMLWriter $writer;
    private \daos\Sources $sourcesDao;
    private \daos\Tags $tagsDao;

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
     * @param array{title: string, spout: string, params: string} $source source
     */
    private function writeSource(array $source): void {
        // retrieve the feed url of the source
        $params = json_decode(html_entity_decode($source['params']), true);
        $feed = $this->spoutLoader->get($source['spout']);
        $feedUrl = $feed !== null ? $feed->getXmlUrl($params) : null;

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
     */
    public function export(): void {
        $this->authentication->ensureIsPrivileged();

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

        /** @var StringKeyedArray<array<array{id: int, title: string, tags: string[], spout: string, params: string, filter: ?string, error: ?string, lastupdate: ?int, lastentry: ?int}>> */
        $taggedSources = new StringKeyedArray();
        $untaggedSources = [];
        foreach ($this->sourcesDao->getAll() as $source) {
            if ($source['tags']) {
                foreach ($source['tags'] as $tag) {
                    if (!isset($taggedSources[$tag])) {
                        $taggedSources[$tag] = [];
                    }
                    $taggedSources[$tag][] = $source;
                }
            } else {
                $untaggedSources[] = $source;
            }
        }

        /** @var StringKeyedArray<string> associate tag names with colors */
        $tagColors = new StringKeyedArray();
        foreach ($this->tagsDao->get() as $tag) {
            $this->logger->debug('OPML export: tag ' . $tag['tag'] . ' has color ' . $tag['color']);
            $tagColors[$tag['tag']] = $tag['color'];
        }

        // generate outline elements for all sources
        foreach ($taggedSources as $tag => $children) {
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
        foreach ($untaggedSources as $source) {
            $this->writeSource($source);
        }

        $this->writer->endElement();  // body

        $this->writer->endDocument();
        $this->logger->debug('finished OPML export');

        // save content as file and suggest file name
        $exportName = 'selfoss-subscriptions-' . date('YmdHis') . '.xml';
        header('Content-Disposition: attachment; filename="' . $exportName . '"');
        header('Content-Type: text/xml; charset=UTF-8');
        echo $this->writer->outputMemory();
    }
}
