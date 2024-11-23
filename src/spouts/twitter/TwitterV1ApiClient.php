<?php

// SPDX-FileCopyrightText: 2011–2016 Tobias Zeising <tobias.zeising@aditu.de>
// SPDX-FileCopyrightText: 2013 Tim Gerundt <tim@gerundt.de>
// SPDX-FileCopyrightText: 2014 Mario Starke <sta.ma@web.de>
// SPDX-FileCopyrightText: 2016–2023 Jan Tojnar <jtojnar@gmail.com>
// SPDX-FileCopyrightText: 2018 Binnette <binnette@gmail.com>
// SPDX-License-Identifier: GPL-3.0-or-later

declare(strict_types=1);

namespace spouts\twitter;

use GuzzleHttp;
use GuzzleHttp\Exception\BadResponseException;
use helpers\HtmlString;
use spouts\Item;
use stdClass;

/**
 * Helpers for accessing Twitter V1 API.
 */
class TwitterV1ApiClient {
    private const GROUPED_ENTITY_TYPES = [
        'hashtags',
        'symbols',
        'user_mentions',
        'urls',
        'media',
    ];

    private GuzzleHttp\Client $client;

    public function __construct(GuzzleHttp\Client $client) {
        $this->client = $client;
    }

    /**
     * Fetch timeline from Twitter API.
     *
     * Assumes client property is initialized to Guzzle client configured to access Twitter.
     *
     * @param string $endpoint API endpoint to use
     * @param array<string, string> $params extra query arguments to pass to the API call
     *
     * @throws \Exception when API request fails
     * @throws GuzzleHttp\Exception\GuzzleException when HTTP request fails for API-unrelated reasons
     *
     * @return \Generator<Item<null>> list of items
     */
    public function fetchTimeline(string $endpoint, array $params = []): iterable {
        try {
            $response = $this->client->get("$endpoint.json", [
                'query' => array_merge([
                    'include_rts' => 1,
                    'count' => 50,
                    'tweet_mode' => 'extended',
                ], $params),
            ]);

            $timeline = json_decode((string) $response->getBody());

            if (isset($timeline->statuses)) {
                $timeline = $timeline->statuses;
            }

            if (!is_array($timeline)) {
                throw new \Exception('Invalid twitter response');
            }

            return $this->getItems($timeline);
        } catch (BadResponseException $e) {
            if ($e->hasResponse()) {
                $body = json_decode((string) $e->getResponse()->getBody());

                if (isset($body->errors)) {
                    $errors = implode(
                        "\n",
                        array_map(
                            fn($error) => $error->message,
                            $body->errors
                        )
                    );

                    throw new \Exception($errors, $e->getCode(), $e);
                }
            }

            throw $e;
        }
    }

    /**
     * @param stdClass[] $timeline list of items
     *
     * @return \Generator<Item<null>> list of items
     */
    private function getItems(array $timeline): iterable {
        foreach ($timeline as $item) {
            $author = $item->user->name;
            $targetItem = $item;
            if (isset($item->retweeted_status)) {
                $targetItem = $item->retweeted_status;
                $author .= ' (RT ' . $targetItem->user->name . ')';
            }

            $id = $item->id_str;
            $title = $this->getTweetTitle($targetItem);
            $content = $this->getContent($targetItem);
            $thumbnail = $this->getThumbnail($targetItem);
            $icon = $this->getTweetIcon($targetItem);
            $link = 'https://twitter.com/' . $item->user->screen_name . '/status/' . $item->id_str;
            // Format of `created_at` field not specified, looks US-centric.
            // https://developer.twitter.com/en/docs/twitter-api/v1/data-dictionary/object-model/tweet
            $date = new \DateTimeImmutable($item->created_at);

            yield new Item(
                $id,
                $title,
                $content,
                $thumbnail,
                $icon,
                $link,
                $date,
                $author,
                null
            );
        }
    }

    private function getTweetTitle(stdClass $item): HtmlString {
        $entities = self::formatEntities($item->entities);
        $tweet = self::replaceEntities($item->full_text, $entities);

        return $tweet;
    }

    private function getContent(stdClass $item): HtmlString {
        $result = '';

        if (isset($item->extended_entities) && isset($item->extended_entities->media) && count($item->extended_entities->media) > 0) {
            foreach ($item->extended_entities->media as $media) {
                if ($media->type === 'photo') {
                    $urlEscaped = htmlspecialchars($media->media_url_https, ENT_QUOTES);
                    $result .= '<p><a href="' . $urlEscaped . ':large"><img src="' . $urlEscaped . ':small" alt=""></a></p>' . PHP_EOL;
                }
            }
        }

        if (isset($item->quoted_status)) {
            $quoted = $item->quoted_status;
            $entities = self::formatEntities($quoted->entities);

            $result .= '<a href="https://twitter.com/' . htmlspecialchars($quoted->user->screen_name, ENT_QUOTES) . '">@' . htmlspecialchars($quoted->user->screen_name) . '</a>:';
            $result .= '<blockquote>' . self::replaceEntities($quoted->full_text, $entities)->getRaw() . '</blockquote>';
        }

        return HtmlString::fromRaw($result);
    }

    private function getTweetIcon(stdClass $item): string {
        return $item->user->profile_image_url_https;
    }

    private function getThumbnail(stdClass $item): ?string {
        if (isset($item->entities->media) && $item->entities->media[0]->type === 'photo') {
            return $item->entities->media[0]->media_url_https;
        }

        return null;
    }

    /**
     * convert URLs, handles and hashtags as links
     *
     * @param string $text unformated text
     * @param array<int, array{text: string, url: string, end: int}> $entities array of entities, indexed by index of their initial Unicode code point
     *
     * @return HtmlString formated text
     */
    private static function replaceEntities(string $text, array $entities): HtmlString {
        /** @var string built text */
        $result = '';
        /** @var int<0, max> number of bytes in text */
        $length = strlen($text);
        /** @var int<0, max> index of the currently processed byte in the text */
        $i = 0;
        /** @var int index of the currently processed Unicode code point in the text */
        $cpi = -1;
        /** @var int index of the final Unicode code point of the last processed entity */
        $skipUntilCp = -1;

        while ($i < $length) {
            $c = $text[$i];

            ++$i;

            // UTF-8 continuation bytes are not counted
            if (!((ord($c) & 0b10000000) && !(ord($c) & 0b01000000))) {
                ++$cpi;
            }

            if ($skipUntilCp <= $cpi) {
                if (isset($entities[$cpi])) {
                    $entity = $entities[$cpi];
                    $appended = '<a href="' . htmlspecialchars($entity['url'], ENT_QUOTES) . '" target="_blank" rel="noreferrer">' . htmlspecialchars($entity['text']) . '</a>';
                    $skipUntilCp = $entity['end'];
                } else {
                    // HTML-special characters like “<” or “&” appear to be already escaped.
                    $appended = $c;
                }

                $result .= $appended;
            }
        }

        return HtmlString::fromRaw($result);
    }

    /**
     * Convert entities returned by Twitter API into more convenient representation
     *
     * @param stdClass $groupedEntities entities returned by Twitter API
     *
     * @return array<int, array{text: string, url: string, end: int}> array of entities, indexed by index of their initial Unicode code point
     */
    private static function formatEntities(stdClass $groupedEntities): array {
        $result = [];

        foreach (self::GROUPED_ENTITY_TYPES as $type) {
            if (!isset($groupedEntities->{$type})) {
                continue;
            }

            $entities = $groupedEntities->{$type};
            foreach ($entities as $entity) {
                /** @var int $start */
                $start = $entity->indices[0];
                $end = $entity->indices[1];
                switch ($type) {
                    case 'hashtags':
                        $result[$start] = [
                            'text' => '#' . $entity->text,
                            'url' => 'https://twitter.com/hashtag/' . urlencode($entity->text),
                            'end' => $end,
                        ];
                        break;
                    case 'symbols':
                        $result[$start] = [
                            'text' => '$' . $entity->text,
                            'url' => 'https://twitter.com/search?q=%24' . urlencode($entity->text),
                            'end' => $end,
                        ];
                        break;
                    case 'user_mentions':
                        $result[$start] = [
                            'text' => '@' . $entity->screen_name,
                            'url' => 'https://twitter.com/' . urlencode($entity->screen_name),
                            'end' => $end,
                        ];
                        break;
                    case 'urls':
                    case 'media':
                        $result[$start] = [
                            'text' => $entity->display_url,
                            'url' => $entity->expanded_url,
                            'end' => $end,
                        ];
                        break;
                }
            }
        }

        return $result;
    }
}
