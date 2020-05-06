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
    const FORMAT_JPEG = 'jpeg';
    const FORMAT_PNG = 'png';

    private static $extensions = [
        self::FORMAT_JPEG => 'jpg',
        self::FORMAT_PNG => 'png',
    ];

    private static $imageTypes = [
        // IANA assigned type
        'image/bmp' => 'bmp',
        'image/gif' => 'gif',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/svg+xml' => 'svg',
        'image/tiff' => 'tif',
        'image/vnd.microsoft.icon' => 'ico',
        // Used by Microsoft applications
        'image/x-icon' => 'ico',
        'image/x-ms-bmp' => 'bmp',
        // Incorrect but sometimes appearing
        'image/ico' => 'ico',
        'image/icon' => 'ico',
        'text/ico' => 'ico',
        'application/ico' => 'ico',
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
     * Get preferred extension for the format.
     *
     * @param self::FORMAT_* $format
     *
     * @return string
     */
    public static function getExtension($format) {
        return self::$extensions[$format];
    }

    /**
     * fetch favicon
     *
     * @param string $url source url
     * @param bool $isHtmlUrl
     * @param ?int $width
     * @param ?int $height
     *
     * @return ?array{string, string} pair of URL and blob containing the image data
     */
    public function fetchFavicon($url, $isHtmlUrl = false, $width = null, $height = null) {
        // try given url
        if ($isHtmlUrl === false) {
            $faviconAsPng = $this->loadImage($url, self::FORMAT_PNG, $width, $height);
            if ($faviconAsPng !== null) {
                return [$url, $faviconAsPng];
            }
        }

        $urlElements = parse_url($url);

        // search on base page for <link rel="shortcut icon" url...
        $html = null;
        try {
            $http = $this->webClient->getHttpClient();
            $response = $http->get($url);
            $html = (string) $response->getBody();
            $effectiveUrl = new Uri(WebClient::getEffectiveUrl($url, $response));

            if ($response->getStatusCode() !== 200) {
                throw new \Exception(substr($html, 0, 512));
            }
        } catch (\Exception $e) {
            $this->logger->debug('icon: failed to get html page: ', ['exception' => $e]);
        }

        if ($html !== null) {
            $shortcutIcons = ImageUtils::parseShortcutIcons($html);
            foreach ($shortcutIcons as $shortcutIcon) {
                $shortcutIconUrl = (string) UriResolver::resolve($effectiveUrl, new Uri($shortcutIcon));

                $faviconAsPng = $this->loadImage($shortcutIconUrl, self::FORMAT_PNG, $width, $height);
                if ($faviconAsPng !== null) {
                    return [$shortcutIconUrl, $faviconAsPng];
                }
            }
        }

        // search domain/favicon.ico
        if (isset($urlElements['scheme']) && isset($urlElements['host'])) {
            $url = $urlElements['scheme'] . '://' . $urlElements['host'] . '/favicon.ico';
            $faviconAsPng = $this->loadImage($url, self::FORMAT_PNG, $width, $height);
            if ($faviconAsPng !== null) {
                return [$url, $faviconAsPng];
            }
        }

        return null;
    }

    /**
     * Load image from URL, optionally resize it and convert it to desired format.
     *
     * @param string $url source url
     * @param self::FORMAT_JPEG|self::FORMAT_PNG $format file format of output file
     * @param ?int $width target width
     * @param ?int $height target height
     *
     * @return ?string blob containing the processed image
     */
    public function loadImage($url, $format = self::FORMAT_PNG, $width = null, $height = null) {
        // load image
        try {
            $data = $this->webClient->request($url);
        } catch (\Exception $e) {
            $this->logger->error("failed to retrieve image $url,", ['exception' => $e]);

            return null;
        }

        $imgInfo = null;

        // get image type
        if (extension_loaded('imagick')) {
            // check for svgz or svg
            if (substr($data, 0, 2) === "\x1f\x8b" && ($d = gzdecode($data) !== false)) {
                $data = $d;
            }

            if (preg_match('#<svg[\s>]#si', $data)) {
                $imgInfo = ['mime' => 'image/svg+xml'];
            }
        }

        if ($imgInfo === null) {
            $imgInfo = getimagesizefromstring($data);
            if ($imgInfo[0] === 0 || $imgInfo[1] === 0) {
                // unable to determine dimensions
                return null;
            }
        }

        $mimeType = isset($imgInfo['mime']) ? strtolower($imgInfo['mime']) : null;
        if ($mimeType !== null && isset(self::$imageTypes[$mimeType])) {
            $type = self::$imageTypes[$mimeType];
        } else {
            return null;
        }

        // convert svg to png/jpeg
        if ($type === 'svg') {
            $image = new \Imagick();
            $image->readImageBlob($data);
            if ($width !== null && $height !== null) {
                $image->resizeImage($width, $height, \Imagick::FILTER_LANCZOS, 1, true);
            }

            if ($format === self::FORMAT_JPEG) {
                $image->setImageFormat('jpeg');
                $image->setImageCompression(\Imagick::COMPRESSION_JPEG);
                $image->setImageCompressionQuality(75);
            } else {
                $image->setImageFormat('png24');
                $image->setImageCompression(\Imagick::COMPRESSION_UNDEFINED);
                $image->setOption('png:compression-level', 9);
            }

            return $image;
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
        if ($format === self::FORMAT_JPEG) {
            $data = $wideImage->asString('jpg', 75);
        } else {
            $data = $wideImage->asString('png', 4, PNG_NO_FILTER);
        }

        return $data;
    }
}
