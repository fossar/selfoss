<?php

declare(strict_types=1);

namespace helpers;

/**
 * Class holding image data and accompanying metadata.
 */
class ImageHolder {
    /** @var string */
    private $data;
    /** @var Image::FORMAT_JPEG|Image::FORMAT_PNG */
    private $format;
    /** @var int */
    private $width;
    /** @var int */
    private $height;

    /**
     * @param Image::FORMAT_JPEG|Image::FORMAT_PNG $format
     */
    public function __construct(string $data, string $format, int $width, int $height) {
        $this->data = $data;
        $this->format = $format;
        $this->width = $width;
        $this->height = $height;
    }

    public function getData(): string {
        return $this->data;
    }

    /**
     * @return Image::FORMAT_JPEG|Image::FORMAT_PNG
     */
    public function getFormat() {
        return $this->format;
    }

    public function getWidth(): int {
        return $this->width;
    }

    public function getHeight(): int {
        return $this->height;
    }
}
