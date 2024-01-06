<?php

declare(strict_types=1);

namespace helpers;

use DateTime;

/**
 * Helper class for loading extern items
 *
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
class ViewHelper {
    private Configuration $configuration;

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
    public static function highlight(string $content, string $searchWords): string {
        if (strlen(trim($searchWords)) === 0) {
            return $content;
        }

        if (preg_match('#^/(?P<regex>.+)/$#', $searchWords, $matches)) {
            $content = preg_replace('/(?!<[^<>])(' . $matches[1] . ')(?![^<>]*>)/', '<span class="found">$0</span>', $content);

            assert($content !== null, 'Regex must be valid.'); // For PHPStan: Will be picked up by error handler.

            return $content;
        }

        $searchWords = Search::splitTerms($searchWords);

        foreach ($searchWords as $word) {
            $content = @preg_replace('/(?!<[^<>])(' . preg_quote($word, '/') . ')(?![^<>]*>)/i', '<span class="found">$0</span>', $content);

            assert($content !== null, 'Regex must be valid.');
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
    public static function lazyimg(string $content): string {
        $content = preg_replace_callback("/<img(?P<pre>[^<]+)src=(?:['\"])(?P<src>[^\"']*)(?:['\"])(?P<post>[^<]*)>/i", function(array $matches) {
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
                $width = $width ?? $height * 4 / 3;
                $height = $height ?? $width * 3 / 4;
            }

            $placeholder = "data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='$width' height='$height'><rect fill='%2395c9c5' width='100%' height='100%'/></svg>";

            return "<img src=\"$placeholder\"{$matches['pre']}data-selfoss-src=\"{$matches['src']}\"{$matches['post']}>";
        }, $content);

        assert($content !== null, 'Regex must be valid');

        return $content;
    }

    /**
     * Proxify imgs through atmos/camo when not https
     *
     * @param  string $content item content
     *
     * @return string          item content
     */
    public function camoflauge(string $content): string {
        if (empty($content)) {
            return $content;
        }

        $camo = new \WillWashburn\Phpamo\Phpamo($this->configuration->camoKey, $this->configuration->camoDomain);

        $content = preg_replace_callback(
            "/<img([^<]+)src=(['\"])([^\"']*)(['\"])([^<]*)>/i",
            fn(array $matches) => '<img' . $matches[1] . 'src=' . $matches[2] . $camo->camoHttpOnly($matches[3]) . $matches[4] . $matches[5] . '>',
            $content
        );

        assert($content !== null, 'Regex must be valid');

        return $content;
    }

    /**
     * Prepare entry as expected by the client.
     *
     * @param array{title: string, content: string, datetime: DateTime, updatetime: DateTime, sourcetitle: string, tags: string[]} $item item to modify
     * @param \controllers\Tags $tagsController tags controller
     * @param ?array<array{tag: string, color: string}> $tags list of tags
     * @param ?string $search search query
     *
     * @return array{title: string, strippedTitle: string, content: string, wordCount: int, lengthWithoutTags: int, datetime: string, updatetime: string, sourcetitle: string, tags: StringKeyedArray<array{backColor: string, foreColor: string}>} modified item
     */
    public function preprocessEntry(array $item, \controllers\Tags $tagsController, ?array $tags = null, ?string $search = null): array {
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
        $item['datetime'] = $item['datetime']->format(DateTime::ATOM);
        $item['updatetime'] = $item['updatetime']->format(DateTime::ATOM);
        $item['lengthWithoutTags'] = strlen($contentWithoutTags);

        return $item;
    }
}
