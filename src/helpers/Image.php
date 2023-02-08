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
    public const FORMAT_JPEG = 'jpeg';
    public const FORMAT_PNG = 'png';

    private static $extensions = [
        self::FORMAT_JPEG => 'jpg',
        self::FORMAT_PNG => 'png',
    ];

    public const IMAGE_TYPES = [
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
     */
    public static function getExtension(string $format): string {
        return self::$extensions[$format];
    }

    /**
     * fetch favicon
     *
     * @param string $url source url
     *
     * @return ?array{string, ImageHolder} pair of URL and blob containing the image data
     */
    public function fetchFavicon(
        string $url,
        bool $isHtmlUrl = false,
        ?int $width = null,
        ?int $height = null
    ): ?array {
        // try given url
        try {
            $http = $this->webClient->getHttpClient();
            $response = $http->get($url);
            $blob = (string) $response->getBody();
            $effectiveUrl = new Uri(WebClient::getEffectiveUrl($url, $response));

            if ($response->getStatusCode() !== 200) {
                throw new \Exception(substr($blob, 0, 512));
            }
        } catch (\Throwable $e) {
            $this->logger->error("icon: failed to retrieve URL $url", ['exception' => $e]);

            return null;
        }

        if ($isHtmlUrl === false) {
            $image = $this->loadImage($blob, self::FORMAT_PNG, $width, $height);
            if ($image !== null) {
                return [$url, $image];
            }
        }

        // When HTML page, search for icon links
        if (preg_match('#^text/html\b#i', $response->getHeaderLine('content-type')) || preg_match('#<html[\s>]#si', $blob)) {
            $shortcutIcons = ImageUtils::parseShortcutIcons($blob);
            foreach ($shortcutIcons as $shortcutIcon) {
                $shortcutIconUrl = (string) UriResolver::resolve($effectiveUrl, new Uri($shortcutIcon));

                try {
                    $data = $this->webClient->request($shortcutIconUrl);
                    $image = $this->loadImage($data, self::FORMAT_PNG, $width, $height);

                    if ($image !== null) {
                        return [$shortcutIconUrl, $image];
                    }
                } catch (\Throwable $e) {
                    $this->logger->error("failed to retrieve image $url,", ['exception' => $e]);
                }
            }
        }

        $urlElements = parse_url($url);

        // search domain/favicon.ico
        if (isset($urlElements['scheme']) && isset($urlElements['host'])) {
            $url = $urlElements['scheme'] . '://' . $urlElements['host'] . '/favicon.ico';
            try {
                $data = $this->webClient->request($url);
                $image = $this->loadImage($data, self::FORMAT_PNG, $width, $height);

                if ($image !== null) {
                    return [$url, $image];
                }
            } catch (\Throwable $e) {
                $this->logger->error("failed to retrieve image $url,", ['exception' => $e]);
            }
        }

        return null;
    }

    /**
     * Load image from URL, optionally resize it and convert it to desired format.
     *
     * @param self::FORMAT_* $format file format of output file
     * @param ?int $width target width
     * @param ?int $height target height
     *
     * @return ?ImageHolder blob containing the processed image
     */
    public function loadImage(
        string $data,
        string $format = self::FORMAT_PNG,
        ?int $width = null,
        ?int $height = null
    ): ?ImageHolder {
        $imgInfo = null;

        // get image type
        if (extension_loaded('imagick')) {
            // check for svgz or svg
            if (substr($data, 0, 2) === "\x1f\x8b" && ($d = gzdecode($data) !== false)) {
                $data = $d;
            }

            if (ImageUtils::detectSvg($data)) {
                $imgInfo = ['mime' => 'image/svg+xml'];
            }
        }

        if ($imgInfo === null) {
            $imgInfo = getimagesizefromstring($data);
            if ($imgInfo === false || $imgInfo[0] === 0 || $imgInfo[1] === 0) {
                // unable to determine dimensions
                return null;
            }
        }

        $mimeType = isset($imgInfo['mime']) ? strtolower($imgInfo['mime']) : null;
        if ($mimeType !== null && isset(self::IMAGE_TYPES[$mimeType])) {
            $type = self::IMAGE_TYPES[$mimeType];
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
                $image->setOption('png:compression-level', '9');
            }

            return new ImageHolder((string) $image, $format, $image->getImageWidth(), $image->getImageHeight());
        }

        // convert ico to png
        if ($type === 'ico') {
            $loader = new IcoFileService();
            try {
                $icon = $loader->fromString($data);
            } catch (\InvalidArgumentException $e) {
                $this->logger->error('Icon is not valid', ['exception' => $e]);

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
        } catch (\Throwable $e) {
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

        return new ImageHolder($data, $format, $wideImage->getWidth(), $wideImage->getHeight());
    }
}
