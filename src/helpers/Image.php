<?php

namespace helpers;

use Elphin\IcoFileLoader\IcoFileService;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
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
            $html = \helpers\WebClient::request($url);
        } catch (\Exception $e) {
            \F3::get('logger')->debug('icon: failed to get html page: ', ['exception' => $e]);
        }

        $shortcutIcon = $this->parseShortcutIcon($html);
        if ($shortcutIcon !== null) {
            $shortcutIcon = (string) UriResolver::resolve(new Uri($url), new Uri($shortcutIcon));

            $faviconAsPng = $this->loadImage($shortcutIcon, $width, $height);
            if ($faviconAsPng !== null) {
                $this->faviconUrl = $shortcutIcon;

                return $faviconAsPng;
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
            $data = \helpers\WebClient::request($url);
        } catch (\Exception $e) {
            \F3::get('logger')->error("failed to retrieve image $url,", ['exception' => $e]);

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
                \F3::get('logger')->error("Icon “{$url}” is not valid", ['exception' => $e]);

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
     * parse shortcut icon from given html
     *
     * @param string $html
     *
     * @return ?string favicon url
     */
    private function parseShortcutIcon($html) {
        $dom = new \DomDocument();
        if (@$dom->loadHTML($html) !== true) {
            return null;
        }

        $xpath = new \DOMXPath($dom);
        $elems = $xpath->query("//link[@rel='apple-touch-icon']");
        if ($elems->length === 0) {
            $elems = $xpath->query("//link[@rel='shortcut icon']");
        }
        if ($elems->length === 0) {
            $elems = $xpath->query("//link[@rel='icon']");
        }

        if ($elems->length > 0) {
            $icon = $elems->item(0);
            if ($icon->hasAttribute('href')) {
                return $icon->getAttribute('href');
            }
        }

        return null;
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
