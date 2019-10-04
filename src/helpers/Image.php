<?php

namespace helpers;

use Elphin\IcoFileLoader\IcoFileService;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use Monolog\Logger;
use WideImage\WideImage;

/**
 * Helper class for loading images
 *
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
class Image {
    /** @var ?string url of last fetched favicon */
    private $faviconUrl = null;

    private static $faviconMimeTypes = [
        // IANA assigned type
        'image/vnd.microsoft.icon',
        // Used by Microsoft applications
        'image/x-icon',
        // Incorrect but sometimes appearing
        'image/ico',
        'image/icon',
        'text/ico',
        'application/ico'
    ];

    private static $iconRelWeights = [
        'apple-touch-icon-precomposed' => 3,
        'apple-touch-icon' => 2,
        'shortcut icon' => 1,
        'icon' => 1,
    ];

    /** @var Logger */
    private $logger;

    /** @var WebClient */
    private $webClient;

    public function __construct(Logger $logger, WebClient $webClient) {
        $this->logger = $logger;
        $this->webClient = $webClient;
    }

    /**
     * fetch favicon
     *
     * @param string $url source url
     * @param bool $isHtmlUrl
     * @param ?int $width
     * @param ?int $height
     *
     * @return ?string
     */
    public function fetchFavicon($url, $isHtmlUrl = false, $width = null, $height = null) {
        // try given url
        if ($isHtmlUrl === false) {
            $faviconAsPng = $this->loadImage($url, $width, $height);
            if ($faviconAsPng !== null) {
                $this->faviconUrl = $url;

                return $faviconAsPng;
            }
        }

        $urlElements = parse_url($url);

        // search on base page for <link rel="shortcut icon" url...
        $html = null;
        try {
            $html = $this->webClient->request($url);
        } catch (\Exception $e) {
            $this->logger->debug('icon: failed to get html page: ', ['exception' => $e]);
        }

        if ($html !== null) {
            $shortcutIcons = self::parseShortcutIcons($html);
            foreach ($shortcutIcons as $shortcutIcon) {
                $shortcutIconUrl = (string) UriResolver::resolve(new Uri($url), new Uri($shortcutIcon));

                $faviconAsPng = $this->loadImage($shortcutIconUrl, $width, $height);
                if ($faviconAsPng !== null) {
                    $this->faviconUrl = $shortcutIconUrl;

                    return $faviconAsPng;
                }
            }
        }

        // search domain/favicon.ico
        if (isset($urlElements['scheme']) && isset($urlElements['host'])) {
            $url = $urlElements['scheme'] . '://' . $urlElements['host'] . '/favicon.ico';
            $faviconAsPng = $this->loadImage($url, $width, $height);
            if ($faviconAsPng !== null) {
                $this->faviconUrl = $url;

                return $faviconAsPng;
            }
        }

        return null;
    }

    /**
     * load image
     *
     * @param string $url source url
     * @param string $extension file extension of output file
     * @param ?int $width
     * @param ?int $height
     *
     * @return ?string
     */
    public function loadImage($url, $extension = 'png', $width = null, $height = null) {
        // load image
        try {
            $data = $this->webClient->request($url);
        } catch (\Exception $e) {
            $this->logger->error("failed to retrieve image $url,", ['exception' => $e]);

            return null;
        }

        // get image type
        $imgInfo = @getimagesizefromstring($data);
        if (in_array(strtolower($imgInfo['mime']), self::$faviconMimeTypes, true)) {
            $type = 'ico';
        } elseif (strtolower($imgInfo['mime']) === 'image/png') {
            $type = 'png';
        } elseif (strtolower($imgInfo['mime']) === 'image/jpeg') {
            $type = 'jpg';
        } elseif (strtolower($imgInfo['mime']) === 'image/gif') {
            $type = 'gif';
        } elseif (strtolower($imgInfo['mime']) === 'image/x-ms-bmp') {
            $type = 'bmp';
        } else {
            return null;
        }

        // convert ico to png
        if ($type === 'ico') {
            $loader = new IcoFileService();
            try {
                $icon = $loader->fromString($data);
            } catch (\InvalidArgumentException $e) {
                $this->logger->error("Icon “{$url}” is not valid", ['exception' => $e]);

                return null;
            }

            $image = null;
            if ($width !== null && $height !== null) {
                $image = $icon->findBestForSize($width, $height);
            }

            if ($image === null) {
                $image = $icon->findBest();
            }

            if ($image === null) {
                return null;
            }

            $data = $loader->renderImage($image);

            ob_start();
            imagepng($data);
            $data = ob_get_contents();
            ob_end_clean();
        }

        // parse image for saving it later
        try {
            $wideImage = WideImage::load($data);
        } catch (\Exception $e) {
            return null;
        }

        // resize
        if ($width !== null && $height !== null) {
            if (($height !== null && $wideImage->getHeight() > $height) ||
               ($width !== null && $wideImage->getWidth() > $width)) {
                $wideImage = $wideImage->resize($width, $height);
            }
        }

        // return image as jpg or png
        if ($extension === 'jpg') {
            $data = $wideImage->asString('jpg', 75);
        } else {
            $data = $wideImage->asString('png', 4, PNG_NO_FILTER);
        }

        return $data;
    }

    /**
     * get favicon url
     *
     * @return ?string
     */
    public function getFaviconUrl() {
        return $this->faviconUrl;
    }

    /**
     * removes $startString, $endString and everything in between from $subject
     *
     * @param string $startString
     * @param string $endString
     * @param string $subject
     *
     * @return string
     */
    public static function cleanString($startString, $endString, $subject) {
        while (false !== $p1 = stripos($subject, $startString)) {
            $block = substr($subject, $p1);
            $subject = substr($subject, 0, $p1);
            if (false !== $p2 = stripos($block, $endString)) {
                $subject .= substr($block, $p2 + strlen($endString));
            }
        }

        return $subject;
    }

    /**
     * parse shortcut icons from given html
     * If icons are found, their URLs are returned in an array ordered by size
     * if given or by likely size of not, with the biggest one first.
     *
     * @param string $html
     *
     * @return array
     */
    public static function parseShortcutIcons($html) {
        $end = strripos($html, '</head>');
        if ($end > 1) {
            $html = substr($html, 0, $end);
        }

        // $html= preg_replace('/<!--.*-->/sU', '', preg_replace('#<(script|style)\b[^>]*>.*</\1>#sU', '', $html));
        // should to the same, but doesn't always.
        $html = self::cleanString('<script', '</script>', $html);
        $html = self::cleanString('<style', '</style>', $html);
        $html = self::cleanString('<!--', '-->', $html);

        if (preg_match_all('#<link\b[^>]*\brel=("|\')(?P<rel>apple-touch-icon|apple-touch-icon-precomposed|shortcut icon|icon)\1[^>]*>#iU', $html, $links, PREG_SET_ORDER) < 1) {
            return [];
        }

        $icons = [];
        $i = 0;
        foreach ($links as $link) {
            if (preg_match('#\shref=("|\')(?P<url>.+)\1#iU', $link[0], $href)) {
                $icons[] = [
                    'url' => $href['url'],
                    'sizes' => preg_match('#\ssizes=("|\')(?P<sizes>[0-9\.]+).*\1#i', $link[0], $sizes)
                        ? $sizes['sizes']
                        : 0,
                    'rel' => $link['rel'],
                    'order' => $i++,
                ];
            }
        }
        if (count($icons) === 0) {
            return $icons;
        }

        usort($icons, Misc::compareBy(
            // largest icons first
            [function($val) {
                return (int) $val['sizes'];
            }, Misc::ORDER_DESC],

            // then by rel priority
            [function($val) {
                return self::$iconRelWeights[$val['rel']];
            }, Misc::ORDER_DESC],

            // and finally by order to make the sorting stable
            'order'
        ));

        return array_map(function($i) {
            return $i['url'];
        }, $icons);
    }

    /**
     * taken from: http://zytzagoo.net/blog/2008/01/23/extracting-images-from-html-using-regular-expressions/
     * Searches for the first occurence of an html <img> element in a string
     * and extracts the src if it finds it. Returns null in case an <img>
     * element is not found.
     *
     * @param string $html An HTML string
     *
     * @return ?string content of the src attribute of the first image
     */
    public static function findFirstImageSource($html) {
        if (stripos($html, '<img') !== false) {
            $imgsrc_regex = '#<\s*img [^\>]*src\s*=\s*(["\'])(.*?)\1#im';
            preg_match($imgsrc_regex, $html, $matches);
            unset($imgsrc_regex);
            unset($html);
            if (is_array($matches) && !empty($matches)) {
                return $matches[2];
            } else {
                return null;
            }
        } else {
            return null;
        }
    }
}
