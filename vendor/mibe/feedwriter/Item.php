<?php
namespace FeedWriter;

use \DateTime;

/*
 * Copyright (C) 2008 Anis uddin Ahmad <anisniit@gmail.com>
 * Copyright (C) 2010-2013, 2015-2016 Michael Bemmerl <mail@mx-server.de>
 *
 * This file is part of the "Universal Feed Writer" project.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Universal Feed Writer
 *
 * Item class - Used as feed element in Feed class
 *
 * @package         UniversalFeedWriter
 * @author          Anis uddin Ahmad <anisniit@gmail.com>
 * @link            http://www.ajaxray.com/projects/rss
 */
class Item
{
    /**
    * Collection of feed item elements
    */
    private $elements = array();

    /**
    * Contains the format of this feed.
    */
    private $version;

    /**
    * Is used as a suffix when multiple elements have the same name.
    **/
    private $_cpt = 0;

    /**
    * Constructor
    *
    * @param string $version constant (RSS1/RSS2/ATOM) RSS2 is default.
    */
    public function __construct($version = Feed::RSS2)
    {
        $this->version = $version;
    }

    /**
    * Return an unique number
    *
    * @access   private
    * @return   int
    **/
    private function cpt()
    {
        return $this->_cpt++;
    }

    /**
    * Add an element to elements array
    *
    * @access   public
    * @param    string $elementName    The tag name of an element
    * @param    string $content        The content of tag
    * @param    array  $attributes     Attributes (if any) in 'attrName' => 'attrValue' format
    * @param    boolean $overwrite     Specifies if an already existing element is overwritten.
    * @param    boolean $allowMultiple Specifies if multiple elements of the same name are allowed.
    * @return   self
    * @throws   \InvalidArgumentException if the element name is not a string, empty or NULL.
    */
    public function addElement($elementName, $content, array $attributes = null, $overwrite = FALSE, $allowMultiple = FALSE)
    {
        if (empty($elementName))
            throw new \InvalidArgumentException('The element name may not be empty or NULL.');
        if (!is_string($elementName))
            throw new \InvalidArgumentException('The element name must be a string.');

        $key = $elementName;

        // return if element already exists & if overwriting is disabled
        // & if multiple elements are not allowed.
        if (isset($this->elements[$elementName]) && !$overwrite) {
            if (!$allowMultiple)
                return $this;

            $key .= '-' . $this->cpt();
        }

        $this->elements[$key]['name']       = $elementName;
        $this->elements[$key]['content']    = $content;
        $this->elements[$key]['attributes'] = $attributes;

        return $this;
    }

    /**
    * Set multiple feed elements from an array.
    * Elements which have attributes cannot be added by this method
    *
    * @access   public
    * @param    array   array of elements in 'tagName' => 'tagContent' format.
    * @return   self
    */
    public function addElementArray(array $elementArray)
    {
        foreach ($elementArray as $elementName => $content) {
            $this->addElement($elementName, $content);
        }

        return $this;
    }

    /**
    * Return the collection of elements in this feed item
    *
    * @access   public
    * @return   array   All elements of this item.
    * @throws   InvalidOperationException on ATOM feeds if either a content or link element is missing.
    * @throws   InvalidOperationException on RSS1 feeds if a title or link element is missing.
    */
    public function getElements()
    {
        // ATOM feeds have some specific requirements...
        if ($this->version == Feed::ATOM)
        {
            // Add an 'id' element, if it was not added by calling the setLink method.
            // Use the value of the title element as key, since no link element was specified.
            if (!array_key_exists('id', $this->elements))
                $this->setId(Feed::uuid($this->elements['title']['content'], 'urn:uuid:'));

            // Either a 'link' or 'content' element is needed.
            if (!array_key_exists('content', $this->elements) && !array_key_exists('link', $this->elements))
                throw new InvalidOperationException('ATOM feed entries need a link or a content element. Call the setLink or setContent method.');
        }
        // ...same with RSS1 feeds.
        else if ($this->version == Feed::RSS1)
        {
            if (!array_key_exists('title', $this->elements))
                throw new InvalidOperationException('RSS1 feed entries need a title element. Call the setTitle method.');
            if (!array_key_exists('link', $this->elements))
                throw new InvalidOperationException('RSS1 feed entries need a link element. Call the setLink method.');
        }

        return $this->elements;
    }

    /**
    * Return the type of this feed item
    *
    * @access   public
    * @return   string  The feed type, as defined in Feed.php
    */
    public function getVersion()
    {
        return $this->version;
    }

    // Wrapper functions ------------------------------------------------------

    /**
    * Set the 'description' element of feed item
    *
    * @access   public
    * @param    string $description The content of the 'description' or 'summary' element
    * @return   self
    */
    public function setDescription($description)
    {
        $tag = ($this->version == Feed::ATOM) ? 'summary' : 'description';

        return $this->addElement($tag, $description);
    }

    /**
    * Set the 'content' element of the feed item
    * For ATOM feeds only
    *
    * @access   public
    * @param    string $content Content for the item (i.e., the body of a blog post).
    * @return   self
    * @throws   InvalidOperationException if this method is called on non-ATOM feeds.
    */
    public function setContent($content)
    {
        if ($this->version != Feed::ATOM)
            throw new InvalidOperationException('The content element is supported in ATOM feeds only.');

        return $this->addElement('content', $content, array('type' => 'html'));
    }

    /**
    * Set the 'title' element of feed item
    *
    * @access   public
    * @param    string $title The content of 'title' element
    * @return   self
    */
    public function setTitle($title)
    {
        return $this->addElement('title', $title);
    }

    /**
    * Set the 'date' element of the feed item.
    *
    * The value of the date parameter can be either an instance of the
    * DateTime class, an integer containing a UNIX timestamp or a string
    * which is parseable by PHP's 'strtotime' function.
    *
    * @access   public
    * @param    DateTime|int|string $date Date which should be used.
    * @return   self
    * @throws   \InvalidArgumentException if the given date was not parseable.
    */
    public function setDate($date)
    {
        if (!is_numeric($date)) {
            if ($date instanceof DateTime)
                $date = $date->getTimestamp();
            else {
                $date = strtotime($date);

                if ($date === FALSE)
                    throw new \InvalidArgumentException('The given date string was not parseable.');
            }
        } elseif ($date < 0)
            throw new \InvalidArgumentException('The given date is not an UNIX timestamp.');

        if ($this->version == Feed::ATOM) {
            $tag    = 'updated';
            $value  = date(\DATE_ATOM, $date);
        } elseif ($this->version == Feed::RSS2) {
            $tag    = 'pubDate';
            $value  = date(\DATE_RSS, $date);
        } else {
            $tag    = 'dc:date';
            $value  = date("Y-m-d", $date);
        }

        return $this->addElement($tag, $value);
    }

    /**
    * Set the 'link' element of feed item
    *
    * @access   public
    * @param    string $link The content of 'link' element
    * @return   self
    */
    public function setLink($link)
    {
        if ($this->version == Feed::RSS2 || $this->version == Feed::RSS1) {
            $this->addElement('link', $link);
        } else {
            $this->addElement('link','',array('href'=>$link));
            $this->setId(Feed::uuid($link,'urn:uuid:'));
        }

        return $this;
    }

    /**
    * Attach a external media to the feed item.
    * Not supported in RSS 1.0 feeds.
    *
    * See RFC 4288 for syntactical correct MIME types.
    *
    * Note that you should avoid the use of more than one enclosure in one item,
    * since some RSS aggregators don't support it.
    *
    * @access   public
    * @param    string $url       The URL of the media.
    * @param    integer $length   The length of the media.
    * @param    string  $type     The MIME type attribute of the media.
    * @param    boolean $multiple Specifies if multiple enclosures are allowed
    * @return   self
    * @link     https://tools.ietf.org/html/rfc4288
    * @throws   \InvalidArgumentException if the length or type parameter is invalid.
    * @throws   InvalidOperationException if this method is called on RSS1 feeds.
    */
    public function addEnclosure($url, $length, $type, $multiple = TRUE)
    {
        if ($this->version == Feed::RSS1)
            throw new InvalidOperationException('Media attachment is not supported in RSS1 feeds.');

        // the length parameter should be set to 0 if it can't be determined
        // see http://www.rssboard.org/rss-profile#element-channel-item-enclosure
        if (!is_numeric($length) || $length < 0)
            throw new \InvalidArgumentException('The length parameter must be an integer and greater or equals to zero.');

        // Regex used from RFC 4287, page 41
        if (!is_string($type) || preg_match('/.+\/.+/', $type) != 1)
            throw new \InvalidArgumentException('type parameter must be a string and a MIME type.');

        $attributes = array('length' => $length, 'type' => $type);

        if ($this->version == Feed::RSS2) {
            $attributes['url'] = $url;
            $this->addElement('enclosure', '', $attributes, FALSE, $multiple);
        } else {
            $attributes['href'] = $url;
            $attributes['rel'] = 'enclosure';
            $this->addElement('atom:link', '', $attributes, FALSE, $multiple);
        }

        return $this;
    }

    /**
    * Set the 'author' element of feed item.
    * Not supported in RSS 1.0 feeds.
    *
    * @access   public
    * @param    string $author The author of this item
    * @param    string|null $email Optional email address of the author
    * @param    string|null $uri Optional URI related to the author
    * @return   self
    * @throws   \InvalidArgumentException if the provided email address is syntactically incorrect.
    * @throws   InvalidOperationException if this method is called on RSS1 feeds.
    */
    public function setAuthor($author, $email = null, $uri = null)
    {
        if ($this->version == Feed::RSS1)
            throw new InvalidOperationException('The author element is not supported in RSS1 feeds.');

        // Regex from RFC 4287 page 41
        if ($email != null && preg_match('/.+@.+/', $email) != 1)
            throw new \InvalidArgumentException('The email address is syntactically incorrect.');

        if ($this->version == Feed::RSS2)
        {
            if ($email != null)
                $author = $email . ' (' . $author . ')';

            $this->addElement('author', $author);
        }
        else
        {
            $elements = array('name' => $author);

            if ($email != null)
                $elements['email'] = $email;

            if ($uri != null)
                $elements['uri'] = $uri;

            $this->addElement('author', $elements);
        }

        return $this;
    }

    /**
    * Set the unique identifier of the feed item
    *
    * On ATOM feeds, the identifier must begin with an valid URI scheme.
    *
    * @access   public
    * @param    string $id         The unique identifier of this item
    * @param    boolean $permaLink The value of the 'isPermaLink' attribute in RSS 2 feeds.
    * @return   self
    * @throws   \InvalidArgumentException if the permaLink parameter is not boolean.
    * @throws   InvalidOperationException if this method is called on RSS1 feeds.
    */
    public function setId($id, $permaLink = false)
    {
        if ($this->version == Feed::RSS2) {
            if (!is_bool($permaLink))
                throw new \InvalidArgumentException('The permaLink parameter must be boolean.');

            $permaLink = $permaLink ? 'true' : 'false';

            $this->addElement('guid', $id, array('isPermaLink' => $permaLink));
        } elseif ($this->version == Feed::ATOM) {
            // Check if the given ID is an valid URI scheme (see RFC 4287 4.2.6)
            // The list of valid schemes was generated from http://www.iana.org/assignments/uri-schemes
            // by using only permanent or historical schemes.
            $validSchemes = array('aaa', 'aaas', 'about', 'acap', 'acct', 'cap', 'cid', 'coap', 'coaps', 'crid', 'data', 'dav', 'dict', 'dns', 'example', 'fax', 'file', 'filesystem', 'ftp', 'geo', 'go', 'gopher', 'h323', 'http', 'https', 'iax', 'icap', 'im', 'imap', 'info', 'ipp', 'ipps', 'iris', 'iris.beep', 'iris.lwz', 'iris.xpc', 'iris.xpcs', 'jabber', 'ldap', 'mailserver', 'mailto', 'mid', 'modem', 'msrp', 'msrps', 'mtqp', 'mupdate', 'news', 'nfs', 'ni', 'nih', 'nntp', 'opaquelocktoken', 'pack', 'pkcs11', 'pop', 'pres', 'prospero', 'reload', 'rtsp', 'rtsps', 'rtspu', 'service', 'session', 'shttp', 'sieve', 'sip', 'sips', 'sms', 'snews', 'snmp', 'soap.beep', 'soap.beeps', 'stun', 'stuns', 'tag', 'tel', 'telnet', 'tftp', 'thismessage', 'tip', 'tn3270', 'turn', 'turns', 'tv', 'urn', 'vemmi', 'videotex', 'vnc', 'wais', 'ws', 'wss', 'xcon', 'xcon-userid', 'xmlrpc.beep', 'xmlrpc.beeps', 'xmpp', 'z39.50', 'z39.50r', 'z39.50s');
            $found = FALSE;
            $checkId = strtolower($id);

            foreach($validSchemes as $scheme)
                if (strrpos($checkId, $scheme . ':', -strlen($checkId)) !== FALSE)
                {
                    $found = TRUE;
                    break;
                }

            if (!$found)
                throw new \InvalidArgumentException("The ID must begin with an IANA-registered URI scheme.");

            $this->addElement('id', $id, NULL, TRUE);
        } else
            throw new InvalidOperationException('A unique ID is not supported in RSS1 feeds.');

        return $this;
    }

 } // end of class Item
