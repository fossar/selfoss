<?php

namespace Tests\Helpers;

use helpers\Image;
use PHPUnit\Framework\TestCase;

final class IconExtractorTest extends TestCase {
    /**
     * Apple touch icons are supported.
     */
    public function testAppleTouchIcon() {
        $page = <<<EOD
<html>
<head>
<link rel="apple-touch-icon" sizes="114x114" href="https://www.example.com/images/apple-touch-114x114.png">
</head>
<body>
</body>
</html>
EOD;

        $this->assertEquals(
            Image::parseShortcutIcon($page),
            'https://www.example.com/images/apple-touch-114x114.png'
        );
    }

    /**
     * Shortcut icons are supported.
     */
    public function testShortcutIcon() {
        $page = <<<EOD
<html>
<head>
<link rel="shortcut icon" href="https://www.example.com/favicon.ico">
</head>
<body>
</body>
</html>
EOD;

        $this->assertEquals(
            Image::parseShortcutIcon($page),
            'https://www.example.com/favicon.ico'
        );
    }

    /**
     * Icons are supported.
     */
    public function testIcon() {
        $page = <<<EOD
<html>
<head>
<link rel="icon" href="https://www.example.com/favicon.ico">
</head>
<body>
</body>
</html>
EOD;

        $this->assertEquals(
            Image::parseShortcutIcon($page),
            'https://www.example.com/favicon.ico'
        );
    }

    /**
     * Apple icons are prioritized over shortcut icons.
     */
    public function testAppleAndIcon() {
        $page = <<<EOD
<html>
<head>
<link rel="icon" href="/favicon.ico">
<link rel="apple-touch-icon" sizes="114x114" href="https://www.example.com/images/apple-touch-114x114.png">
</head>
<body>
</body>
</html>
EOD;

        $this->assertEquals(
            Image::parseShortcutIcon($page),
            'https://www.example.com/images/apple-touch-114x114.png'
        );
    }

    /**
     * Null is returned when no icon found.
     */
    public function testMissingIcon() {
        $page = <<<EOD
<html>
<head>
</head>
<body>
</body>
</html>
EOD;

        $this->assertNull(
            Image::parseShortcutIcon($page)
        );
    }

    /**
     * Commented out icons are ignored.
     */
    public function testCommentIcon() {
        $page = <<<EOD
<html>
<head>
<!--<link rel="icon" href="https://www.example.com/favicon.ico">-->
</head>
<body>
</body>
</html>
EOD;

        $this->assertNull(
            Image::parseShortcutIcon($page)
        );
    }

    /**
     * Icons inside script elements are ignored.
     */
    public function testScriptIcon() {
        $page = <<<EOD
<html>
<head>
<script>
console.log('<link rel="icon" href="https://www.example.com/favicon.ico">');
</script>
</head>
<body>
</body>
</html>
EOD;

        $this->assertNull(
            Image::parseShortcutIcon($page)
        );
    }
}
