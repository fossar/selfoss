<?php

declare(strict_types=1);

namespace helpers;

/**
 * Class holding image data and accompanying metadata.
 */
class ImageHolder {
    private string $data;
    /** @var Image::FORMAT_JPEG|Image::FORMAT_PNG */
    private string $format;
    private int $width;
    private int $height;

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
