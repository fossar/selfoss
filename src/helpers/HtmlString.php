<?php

declare(strict_types=1);

namespace Selfoss\helpers;

/**
 * A string wrapper representing a HTML fragment.
 */
class HtmlString {
    private string $content;

    private function __construct(string $content) {
        $this->content = $content;
    }

    /**
     * Creates a new HtmlString from a string containing a HTML fragment.
     */
    public static function fromRaw(string $content): self {
        return new self($content);
    }

    /**
     * Creates a new HtmlString from a plain text string.
     */
    public static function fromPlainText(string $content): self {
        return new self(htmlspecialchars($content, ENT_NOQUOTES));
    }

    /**
     * Returns a HTML fragment represented by the object.
     */
    public function getRaw(): string {
        return $this->content;
    }

    /**
     * Returns a plain text without any HTML tags.
     */
    public function getPlainText(): string {
        return htmlspecialchars_decode(strip_tags($this->content));
    }
}
