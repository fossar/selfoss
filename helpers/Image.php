<?php

namespace helpers;

use WideImage\WideImage;

/**
 * Helper class for loading images
 *
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
class Image {
    /** @var string url of last fetched favicon */
    private $faviconUrl = false;

    /**
     * fetch favicon
     *
     * @param string $url source url
     *
     * @return bool
     */
    public function fetchFavicon($url, $isHtmlUrl = false, $width = false, $height = false) {
        // try given url
        if ($isHtmlUrl == false) {
            $faviconAsPng = $this->loadImage($url, $width, $height);
            if ($faviconAsPng !== false) {
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
            $shortcutIcon = (string) \SimplePie_IRI::absolutize($url, $shortcutIcon);

            $faviconAsPng = $this->loadImage($shortcutIcon, $width, $height);
            if ($faviconAsPng !== false) {
                $this->faviconUrl = $shortcutIcon;

                return $faviconAsPng;
            }
        }

        // search domain/favicon.ico
        if (isset($urlElements['scheme']) && isset($urlElements['host'])) {
            $url = $urlElements['scheme'] . '://' . $urlElements['host'] . '/favicon.ico';
            $faviconAsPng = $this->loadImage($url, $width, $height);
            if ($faviconAsPng !== false) {
                $this->faviconUrl = $url;

                return $faviconAsPng;
            }
        }

        return false;
    }

    /**
     * load image
     *
     * @param string $url source url
     * @param string $extension file extension of output file
     * @param int $width
     * @param int $height
     *
     * @return bool
     */
    public function loadImage($url, $extension = 'png', $width = false, $height = false) {
        // load image
        try {
            $data = \helpers\WebClient::request($url);
        } catch (\Exception $e) {
            \F3::get('logger')->error("failed to retrieve image $url,", ['exception' => $e]);

            return false;
        }

        // get image type
        $tmp = \F3::get('cache') . '/' . md5($url);
        file_put_contents($tmp, $data);
        $imgInfo = @getimagesize($tmp);
        if (strtolower($imgInfo['mime']) == 'image/vnd.microsoft.icon') {
            $type = 'ico';
        } elseif (strtolower($imgInfo['mime']) == 'image/png') {
            $type = 'png';
        } elseif (strtolower($imgInfo['mime']) == 'image/jpeg') {
            $type = 'jpg';
        } elseif (strtolower($imgInfo['mime']) == 'image/gif') {
            $type = 'gif';
        } elseif (strtolower($imgInfo['mime']) == 'image/x-ms-bmp') {
            $type = 'bmp';
        } else {
            @unlink($tmp);

            return false;
        }

        // convert ico to png
        if ($type == 'ico') {
            $ico = new \floIcon();
            @$ico->readICO($tmp);
            if (count($ico->images) == 0) {
                @unlink($tmp);

                return false;
            }
            ob_start();
            @imagepng($ico->images[count($ico->images) - 1]->getImageResource());
            $data = ob_get_contents();
            ob_end_clean();
        }

        // parse image for saving it later
        @unlink($tmp);
        try {
            $wideImage = WideImage::load($data);
        } catch (\Exception $e) {
            return false;
        }

        // resize
        if ($width !== false && $height !== false) {
            if (($height !== null && $wideImage->getHeight() > $height) ||
               ($width !== null && $wideImage->getWidth() > $width)) {
                $wideImage = $wideImage->resize($width, $height);
            }
        }

        // return image as jpg or png
        if ($extension == 'jpg') {
            $data = $wideImage->asString('jpg', 75);
        } else {
            $data = $wideImage->asString('png', 4, PNG_NO_FILTER);
        }

        return $data;
    }

    /**
     * get favicon url
     *
     * @return string
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
}
