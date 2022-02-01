<?php

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
     * @param string $data
     * @param Image::FORMAT_JPEG|Image::FORMAT_PNG $format
     * @param int $width
     * @param int $height
     */
    public function __construct($data, $format, $width, $height) {
        $this->data = $data;
        $this->format = $format;
        $this->width = $width;
        $this->height = $height;
    }

    /**
     * @return string
     */
    public function getData() {
        return $this->data;
    }

    /**
     * @return Image::FORMAT_JPEG|Image::FORMAT_PNG
     */
    public function getFormat() {
        return $this->format;
    }

    /**
     * @return int
     */
    public function getWidth() {
        return $this->width;
    }

    /**
     * @return int
     */
    public function getHeight() {
        return $this->height;
    }
}
