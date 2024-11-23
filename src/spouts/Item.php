<?php

declare(strict_types=1);

namespace spouts;

use DateTimeInterface;
use helpers\HtmlString;

/**
 * Value object representing a source item (e.g. an article).
 *
 * @template-covariant Extra type of extra data
 */
class Item {
    /** @var string an unique id for this item */
    private string $id;

    private HtmlString $title;

    /** @var HtmlString|(callable(static): HtmlString) content */
    private $content;

    private ?string $thumbnail = null;

    /** @var (?string)|(callable(static): ?string) icon */
    private $icon;

    private string $link;

    private ?DateTimeInterface $date = null;

    private ?string $author = null;

    /** @var Extra extra data */
    private $extraData;

    /**
     * @param Extra $extraData
     * @param HtmlString|(callable(static): HtmlString) $content
     * @param (?string)|(callable(static): ?string) $icon
     */
    public function __construct(
        string $id,
        HtmlString $title,
        $content,
        ?string $thumbnail,
        $icon,
        string $link,
        ?DateTimeInterface $date,
        ?string $author,
        $extraData
    ) {
        $this->id = $id;
        $this->title = $title;
        $this->content = $content;
        $this->thumbnail = $thumbnail;
        $this->icon = $icon;
        $this->link = $link;
        $this->date = $date;
        $this->author = $author;
        $this->extraData = $extraData;
    }

    /**
     * Returns an ID for this article.
     *
     * It should be unique for the source.
     */
    public function getId(): string {
        return $this->id;
    }

    /**
     * @return static
     */
    public function withId(string $id): self {
        $modified = clone $this;
        $modified->id = $id;

        return $modified;
    }

    /**
     * Returns the title of the article.
     *
     * If the spout allows HTML in the title, HTML special chars are expected to be decoded by the spout
     * (for instance when the spout feed is XML).
     */
    public function getTitle(): HtmlString {
        return $this->title;
    }

    /**
     * @return static
     */
    public function withTitle(HtmlString $title): self {
        $modified = clone $this;
        $modified->title = $title;

        return $modified;
    }

    /**
     * Returns the content of the article.
     *
     * HTML special chars are expected to be decoded by the spout
     * (for instance when the spout feed is XML).
     */
    public function getContent(): HtmlString {
        if (is_callable($this->content)) {
            $oldContent = $this->content;
            // Temporarily unset so that calling getContent from the callback does not create an infinite loop.
            $this->content = HtmlString::fromRaw('');
            $this->content = $oldContent($this);
        }

        return $this->content;
    }

    /**
     * @param HtmlString|(callable(static): HtmlString) $content
     *
     * @return static
     */
    public function withContent($content): self {
        $modified = clone $this;
        $modified->content = $content;

        return $modified;
    }

    /**
     * Returns the URL of a thumbnail (for multimedia feeds).
     */
    public function getThumbnail(): ?string {
        return $this->thumbnail;
    }

    /**
     * @return static
     */
    public function withThumbnail(?string $thumbnail): self {
        $modified = clone $this;
        $modified->thumbnail = $thumbnail;

        return $modified;
    }

    /**
     * Returns the URL for favicon of the article.
     */
    public function getIcon(): ?string {
        if (is_callable($this->icon)) {
            $oldIcon = $this->icon;
            // Temporarily unset so that calling getIcon from the callback does not create an infinite loop.
            $this->icon = null;
            $this->icon = $oldIcon($this);
        }

        return $this->icon;
    }

    /**
     * @param (?string)|(callable(static): ?string) $icon
     *
     * @return static
     */
    public function withIcon($icon): self {
        $modified = clone $this;
        $modified->icon = $icon;

        return $modified;
    }

    /**
     * Returns the direct link to the article.
     */
    public function getLink(): string {
        return $this->link;
    }

    /**
     * @return static
     */
    public function withLink(string $link): self {
        $modified = clone $this;
        $modified->link = $link;

        return $modified;
    }

    /**
     * Returns the publication date of the article.
     */
    public function getDate(): ?DateTimeInterface {
        return $this->date;
    }

    /**
     * @return static
     */
    public function withDate(?DateTimeInterface $date = null): self {
        $modified = clone $this;
        $modified->date = $date;

        return $modified;
    }

    /**
     * Returns the author of the article.
     *
     * HTML special chars decoded, if applicable.
     */
    public function getAuthor(): ?string {
        return $this->author;
    }

    /**
     * @return static
     */
    public function withAuthor(?string $author): self {
        $modified = clone $this;
        $modified->author = $author;

        return $modified;
    }

    /**
     * Returns extra data associated with the item.
     *
     * @return Extra
     */
    public function getExtraData() {
        return $this->extraData;
    }

    /**
     * @template NewExtra
     *
     * @param NewExtra $extraData
     *
     * @return Item<NewExtra>
     */
    public function withExtraData($extraData): Item {
        return new self(
            $this->id,
            $this->title,
            $this->content,
            $this->thumbnail,
            $this->icon,
            $this->link,
            $this->date,
            $this->author,
            // Replace with a new value (possibly using a different type).
            $extraData
        );
    }
}
