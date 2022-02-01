<?php

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
     * @param string $id
     * @param string $title
     * @param string $content
     * @param ?string $thumbnail
     * @param ?string $icon
     * @param string $link
     * @param ?DateTimeInterface $date
     * @param ?string $author
     * @param ?Extra $extraData
     */
    public function __construct(
        $id,
        $title,
        $content,
        $thumbnail,
        $icon,
        $link,
        $date,
        $author,
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
     *
     * @return string
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @param string $id
     *
     * @return self
     */
    public function withId($id) {
        $modified = clone $this;
        $modified->id = $id;

        return $modified;
    }

    /**
     * Returns the title of the article.
     *
     * If the spout allows HTML in the title, HTML special chars are expected to be decoded by the spout
     * (for instance when the spout feed is XML).
     *
     * @return string
     */
    public function getTitle() {
        return $this->title;
    }

    /**
     * @param string $title
     *
     * @return self
     */
    public function withTitle($title) {
        $modified = clone $this;
        $modified->title = $title;

        return $modified;
    }

    /**
     * Returns the content of the article.
     *
     * HTML special chars are expected to be decoded by the spout
     * (for instance when the spout feed is XML).
     *
     * @return string
     */
    public function getContent() {
        return $this->content;
    }

    /**
     * @param string $content
     *
     * @return self
     */
    public function withContent($content) {
        $modified = clone $this;
        $modified->content = $content;

        return $modified;
    }

    /**
     * Returns the URL of a thumbnail (for multimedia feeds).
     *
     * @return ?string
     */
    public function getThumbnail() {
        return $this->thumbnail;
    }

    /**
     * @param ?string $thumbnail
     *
     * @return self
     */
    public function withThumbnail($thumbnail) {
        $modified = clone $this;
        $modified->thumbnail = $thumbnail;

        return $modified;
    }

    /**
     * Returns the URL for favicon of the article.
     *
     * @return ?string
     */
    public function getIcon() {
        return $this->icon;
    }

    /**
     * @param ?string $icon
     *
     * @return self
     */
    public function withIcon($icon) {
        $modified = clone $this;
        $modified->icon = $icon;

        return $modified;
    }

    /**
     * Returns the direct link to the article.
     *
     * @return string
     */
    public function getLink() {
        return $this->link;
    }

    /**
     * @param string $link
     *
     * @return self
     */
    public function withLink($link) {
        $modified = clone $this;
        $modified->link = $link;

        return $modified;
    }

    /**
     * Returns the publication date of the article.
     *
     * @return ?DateTimeInterface
     */
    public function getDate() {
        return $this->date;
    }

    /**
     * @param ?DateTimeInterface $date
     *
     * @return self
     */
    public function withDate(DateTimeInterface $date = null) {
        $modified = clone $this;
        $modified->date = $date;

        return $modified;
    }

    /**
     * Returns the author of the article.
     *
     * HTML special chars decoded, if applicable.
     *
     * @return ?string
     */
    public function getAuthor() {
        return $this->author;
    }

    /**
     * @param ?string $author
     *
     * @return self
     */
    public function withAuthor($author) {
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
    public function withExtraData($extraData) {
        $modified = clone $this;
        $modified->extraData = $extraData;

        return $modified;
    }
}
