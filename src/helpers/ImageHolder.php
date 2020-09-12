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

    public function __construct($data, $format, $width, $height) {
        $this->data = $data;
        $this->format = $format;
        $this->width = $width;
        $this->height = $height;
    }

    public function getData() {
        return $this->data;
    }

    public function getFormat() {
        return $this->format;
    }

    public function getWidth() {
        return $this->width;
    }

    public function getHeight() {
        return $this->height;
    }
}
