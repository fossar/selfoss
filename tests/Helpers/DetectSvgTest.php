<?php

namespace Tests\Helpers;

use helpers\ImageUtils;
use PHPUnit\Framework\TestCase;

final class DetectSvgTest extends TestCase {
    /**
     * Detects a basic SVG file correctly.
     */
    public function testBasic(): void {
        $blob = <<<EOD
<svg xmlns="http://www.w3.org/2000/svg" width="400" height="300"><rect fill="%2395c9c5" width="100%" height="100%"/></svg>
EOD;

        $this->assertTrue(
            ImageUtils::detectSvg($blob)
        );
    }

    /**
     * Detects a SVG file with XML directives correctly.
     */
    public function testDirectives(): void {
        $blob = <<<EOD
<?xml version="1.0"?>
<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN"
 "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">

<svg xmlns="http://www.w3.org/2000/svg" width="400" height="300"><rect fill="%2395c9c5" width="100%" height="100%"/></svg>
EOD;

        $this->assertTrue(
            ImageUtils::detectSvg($blob)
        );
    }

    /**
     * Detects a SVG embedded in HTML file correctly.
     */
    public function testInHtml(): void {
        $blob = <<<EOD
<html>
<svg xmlns="http://www.w3.org/2000/svg" width="400" height="300"><rect fill="%2395c9c5" width="100%" height="100%"/></svg>
EOD;

        $this->assertFalse(
            ImageUtils::detectSvg($blob)
        );
    }

    /**
     * Detects a SVG embedded in HTML file with a doctype correctly.
     */
    public function testInHtmlWithDoctype(): void {
        $blob = <<<EOD
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "//www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"><html class="article-page"><head><title>Foo</title></head><svg xmlns="http://www.w3.org/2000/svg" width="400" height="300"><rect fill="%2395c9c5" width="100%" height="100%"/></svg>
EOD;

        $this->assertFalse(
            ImageUtils::detectSvg($blob)
        );
    }

    /**
     * Detects a SVG embedded in HTML file with just a body correctly.
     */
    public function testInHtmlWithBody(): void {
        $blob = <<<EOD
<body><svg xmlns="http://www.w3.org/2000/svg" width="400" height="300"><rect fill="%2395c9c5" width="100%" height="100%"/></svg>
EOD;

        $this->assertFalse(
            ImageUtils::detectSvg($blob)
        );
    }
}
