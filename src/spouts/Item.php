<?php

declare(strict_types=1);

namespace spouts;

use DateTimeInterface;

/**
 * Value object representing a source item (e.g. an article).
 *
 * @template Extra type of extra data
 */
class Item {
    /** @var string an unique id for this item */
    private $id;

    /** @var string title */
    private $title;

    /** @var string content */
    private $content;

    /** @var ?string thumbnail */
    private $thumbnail;

    /** @var ?string icon */
    private $icon;

    /** @var string link */
    private $link;

    /** @var ?DateTimeInterface date */
    private $date;

    /** @var ?string author */
    private $author;

    /** @var ?Extra extra data */
    private $extraData;

    /**
     * @param ?Extra $extraData
     */
    public function __construct(
        string $id,
        string $title,
        string $content,
        ?string $thumbnail,
        ?string $icon,
        string $link,
        ?DateTimeInterface $date,
        ?string $author,
        $extraData = null
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
    public function getTitle(): string {
        return $this->title;
    }

    public function withTitle(string $title): self {
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
    public function getContent(): string {
        return $this->content;
    }

    public function withContent(string $content): self {
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

    public function withThumbnail(?string $thumbnail): self {
        $modified = clone $this;
        $modified->thumbnail = $thumbnail;

        return $modified;
    }

    /**
     * Returns the URL for favicon of the article.
     */
    public function getIcon(): ?string {
        return $this->icon;
    }

    public function withIcon(?string $icon): self {
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
        $modified = clone $this;
        $modified->extraData = $extraData;

        return $modified;
    }
}
