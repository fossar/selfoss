<?php

namespace helpers;

/**
 * Helper class for loading extern items
 *
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
class ViewHelper {
    /** @var Configuration configuration */
    private $configuration;

    public function __construct(Configuration $configuration) {
        $this->configuration = $configuration;
    }

    /**
     * Enclose all searchWords with <span class="found">$word</span>
     * for later highlighing with CSS
     *
     * @param string $content which contains words
     * @param string $searchWords words for highlighting
     *
     * @return string with highlited words
     */
    public static function highlight($content, $searchWords) {
        if (strlen(trim($searchWords)) === 0) {
            return $content;
        }

        if (preg_match('#^/(?P<regex>.+)/$#', $searchWords, $matches)) {
            return preg_replace('/(?!<[^<>])(' . $matches[1] . ')(?![^<>]*>)/', '<span class="found">$0</span>', $content);
        }

        $searchWords = \helpers\Search::splitTerms($searchWords);

        foreach ($searchWords as $word) {
            $content = preg_replace('/(?!<[^<>])(' . preg_quote($word, '/') . ')(?![^<>]*>)/i', '<span class="found">$0</span>', $content);
        }

        return $content;
    }

    /**
     * removes img src attribute and saves the value in data attribute for
     * loading it later
     *
     * @param string $content which contains img tags
     *
     * @return string with replaced img tags
     */
    public static function lazyimg($content) {
        return preg_replace_callback("/<img(?P<pre>[^<]+)src=(?:['\"])(?P<src>[^\"']*)(?:['\"])(?P<post>[^<]*)>/i", function(array $matches) {
            $width = null;
            $height = null;

            $attrs = "{$matches['pre']} {$matches['post']}";
            if (preg_match('/\bwidth=([\'"]?)(?P<width>[0-9]+)\1/i', $attrs, $widthAttr)) {
                $width = (int) $widthAttr['width'];
            }
            if (preg_match('/\bheight=([\'"]?)(?P<height>[0-9]+)\1/i', $attrs, $heightAttr)) {
                $height = (int) $heightAttr['height'];
            }

            if ($width === null && $height === null) {
                // no dimensions provided, assume a 4:3 photo
                $width = 800;
                $height = 600;
            } else {
                // assume a 4:3 photo
                $width = $width === null ? $height * 4 / 3 : $width;
                $height = $height === null ? $width * 3 / 4 : $height;
            }

            $placeholder = "data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='$width' height='$height'><rect fill='%2395c9c5' width='100%' height='100%'/></svg>";

            return "<img src=\"$placeholder\"{$matches['pre']}data-selfoss-src=\"{$matches['src']}\"{$matches['post']}>";
        }, $content);
    }

    /**
     * Return ISO8601 formatted date
     *
     * @param string $datestr sql date
     *
     * @return string
     */
    public static function date_iso8601($datestr) {
        $date = new \DateTime($datestr);

        return $date->format(\DateTime::ATOM);
    }

    /**
     * Proxify imgs through atmos/camo when not https
     *
     * @param  string $content item content
     *
     * @return string          item content
     */
    public function camoflauge($content) {
        if (empty($content)) {
            return $content;
        }

        $camo = new \WillWashburn\Phpamo\Phpamo($this->configuration->camoKey, $this->configuration->camoDomain);

        return preg_replace_callback("/<img([^<]+)src=(['\"])([^\"']*)(['\"])([^<]*)>/i", function(array $matches) use ($camo) {
            return '<img' . $matches[1] . 'src=' . $matches[2] . $camo->camoHttpOnly($matches[3]) . $matches[4] . $matches[5] . '>';
        }, $content);
    }

    /**
     * Prepare entry as expected by the client.
     *
     * @param array $item item to modify
     * @param \controllers\Tags $tagsController tags controller
     * @param ?array $tags list of tags
     * @param ?string $search search query
     *
     * @return array modified item
     */
    public function preprocessEntry(array $item, \controllers\Tags $tagsController, array $tags = null, $search = null) {
        // parse tags and assign tag colors
        $item['tags'] = $tagsController->tagsAddColors($item['tags'], $tags);

        $item['content'] = str_replace('&#34;', '"', $item['content']);

        // highlight search results
        if (isset($search)) {
            $item['sourcetitle'] = ViewHelper::highlight($item['sourcetitle'], $search);
            $item['title'] = ViewHelper::highlight($item['title'], $search);
            $item['content'] = ViewHelper::highlight($item['content'], $search);
        }

        if ($this->configuration->camoKey != '') {
            $item['content'] = $this->camoflauge($item['content']);
        }

        $item['title'] = ViewHelper::lazyimg($item['title']);
        $item['strippedTitle'] = htmLawed(htmlspecialchars_decode($item['title']), ['deny_attribute' => '*', 'elements' => '-*']);
        $item['content'] = ViewHelper::lazyimg($item['content']);
        $contentWithoutTags = strip_tags($item['content']);
        $item['wordCount'] = str_word_count($contentWithoutTags);
        $item['datetime'] = $item['datetime']->format(\DateTime::ATOM);
        $item['updatetime'] = $item['updatetime']->format(\DateTime::ATOM);
        $item['lengthWithoutTags'] = strlen($contentWithoutTags);

        return $item;
    }
}
