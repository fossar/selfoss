<?php

namespace Tests\Helpers;

use helpers\ViewHelper;
use PHPUnit\Framework\TestCase;

final class ImageLazifierTest extends TestCase {
    /**
     * Check that src attribute is renamed, other attributes are preserved and a properly-sized placeholder is chosen.
     */
    public function testBasic(): void {
        $input = <<<EOD
<img foo bar src="https://example.org/example.jpg" alt="" width="900" height="400">
EOD;
        $expected = <<<EOD
<img src="data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='900' height='400'><rect fill='%2395c9c5' width='100%' height='100%'/></svg>" foo bar data-selfoss-src="https://example.org/example.jpg" alt="" width="900" height="400">
EOD;

        $this->assertEquals(
            $expected,
            ViewHelper::lazyimg($input)
        );
    }

    /**
     * Check that width for the placeholder is calculated from height.
     */
    public function testWidthMissing(): void {
        $input = <<<EOD
<img src="https://example.org/example.jpg" height="300">
EOD;
        $expected = <<<EOD
<img src="data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='400' height='300'><rect fill='%2395c9c5' width='100%' height='100%'/></svg>" data-selfoss-src="https://example.org/example.jpg" height="300">
EOD;

        $this->assertEquals(
            $expected,
            ViewHelper::lazyimg($input)
        );
    }

    /**
     * Check that height for the placeholder is calculated from width.
     */
    public function testHeightMissing(): void {
        $input = <<<EOD
<img src="https://example.org/example.jpg" width="400">
EOD;
        $expected = <<<EOD
<img src="data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='400' height='300'><rect fill='%2395c9c5' width='100%' height='100%'/></svg>" data-selfoss-src="https://example.org/example.jpg" width="400">
EOD;

        $this->assertEquals(
            $expected,
            ViewHelper::lazyimg($input)
        );
    }

    /**
     * Check that placeholder dimensions are chosen even when the image does not specify any.
     */
    public function testDimensionsMissing(): void {
        $input = <<<EOD
<img src="https://example.org/example.jpg">
EOD;
        $expected = <<<EOD
<img src="data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='800' height='600'><rect fill='%2395c9c5' width='100%' height='100%'/></svg>" data-selfoss-src="https://example.org/example.jpg">
EOD;

        $this->assertEquals(
            $expected,
            ViewHelper::lazyimg($input)
        );
    }
}
