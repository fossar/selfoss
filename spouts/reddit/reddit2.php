<?php

namespace spouts\reddit;

/**
 * Spout for fetching from reddit 
 *
 * @package    spouts
 * @subpackage reddit
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
class reddit2 extends \spouts\spout {

    /**
     * name of spout
     *
     * @var string
     */
    public $name = 'Reddit';


    /**
     * description of this source type
     *
     * @var string
     */
    public $description = 'Get your fix from Reddit';


    /**
     * config params
     * array of arrays with name, type, default value, required, validation type
     *
     * - Values for type: text, password, checkbox, select
     * - Values for validation: alpha, email, numeric, int, alnum, notempty
     *
     * When type is "select", a new entry "values" must be supplied, holding
     * key/value pairs of internal names (key) and displayed labels (value).
     * See /spouts/rss/heise for an example.
     *
     * e.g.
     * array(
     *   "id" => array(
     *     "title"      => "URL",
     *     "type"       => "text",
     *     "default"    => "",
     *     "required"   => true,
     *     "validation" => array("alnum")
     *   ),
     *   ....
     * )
     *
     * @var bool|mixed
     */
    public $params = array(
        "url" => array(
            "title"      => "Subreddit or multireddit url",
            "type"       => "text",
            "default"    => "r/worldnews/top",
            "required"   => true,
            "validation" => array( "notempty" )
        ),
        "username" => array(
            "title"      => "Username",
            "type"       => "text",
            "default"    => "",
            "required"   => false,
            "validation" => ""
        ),
        "password" => array(
            "title"      => "Password",
            "type"       => "password",
            "default"    => "",
            "required"   => false,
            "validation" => ""
        )
    );


    /**
     * the readability api key
     *
     * @var string
     */
    private $apiKey = "";


    /**
     * the reddit_session cookie
     *
     * @var string
     */
    private $reddit_session = "";


    /**
     * the scrape urls
     *
     * @var string
     */
    private $scrape = true;


    /**
     * current fetched items
     *
     * @var array|bool
     */
    protected $items = false;

    /**
     * favicon url
     *
     * @var string
     */
    private $faviconUrl = '';

    /**
     * loads content for given source
     *
     * @return void
     * @param string  $url
     */
    public function load( $params ) {

        $this->apiKey = \F3::get( 'readability' );
        
        if (!empty($params['password']) && !empty($params['username'])) {
            if (function_exists("apc_fetch")) {
                $this->reddit_session = apc_fetch("{$params['username']}_slefoss_reddit_session");
                if (empty($this->reddit_session)) {
                    $this->login($params);
                }
            }else{
                 $this->login($params);
            }
        }
        $json = json_decode( $this->file_get_contents_curl( "http://www.reddit.com/" . $params['url'] . ".json" ) );
        $this->items = $json->data->children;
    }

    //
    // Iterator Interface
    //

    /**
     * reset iterator
     *
     * @return void
     */
    public function rewind() {
        if ( $this->items!==false )
            reset( $this->items );
    }


    /**
     * receive current item
     *
     * @return SimplePie_Item current item
     */
    public function current() {
        if ( $this->items!==false )
            return $this;
        return false;
    }


    /**
     * receive key of current item
     *
     * @return mixed key of current item
     */
    public function key() {
        if ( $this->items!==false )
            return key( $this->items );
        return false;
    }


    /**
     * select next item
     *
     * @return SimplePie_Item next item
     */
    public function next() {
        if ( $this->items!==false )
            next( $this->items );
        return $this;
    }


    /**
     * end reached
     *
     * @return bool false if end reached
     */
    public function valid() {
        if ( $this->items!==false )
            return current( $this->items ) !== false;
        return false;
    }


    /**
     * returns an unique id for this item
     *
     * @return string id as hash
     */
    public function getId() {
        if ( $this->items!==false && $this->valid() ) {
            $id = @current( $this->items )->data->id;
            if ( strlen( $id )>255 )
                $id = md5( $id );
            return $id;
        }
        return false;
    }


    /**
     * returns the current title as string
     *
     * @return string title
     */
    public function getTitle() {
        if ( $this->items!==false && $this->valid() )
            return @current( $this->items )->data->title;
        return false;
    }

    /**
     * returns the current title as string
     *
     * @return string title
     */
    public function getHtmlUrl() {
        if ( $this->items!==false && $this->valid() ) {
            if ( preg_match( "/imgur/", @current( $this->items )->data->url ) ) {
                if ( !preg_match( '/\.(?:gif|jpg|png|svg)$/i', @current( $this->items )->data->url ) ) {
                    $head = $this->get_head(@current( $this->items )->data->url . ".jpg");
                    if (preg_match( "/404 Not Found/",$head)) {
                        return @current( $this->items )->data->url . "/embed";
                    }
                    return @current( $this->items )->data->url . ".jpg";
                }
            }
            return @current( $this->items )->data->url;
        }
        return false;
    }


    /**
     * returns the content of this item
     *
     * @return string content
     */
    public function getContent() {
        if ( $this->items!==false && $this->valid() ) {
            $text = @current( $this->items )->data->selftext_html;
            if (!empty($text)) {
                return $text;
            }

            if ( preg_match( '/\.(?:gif|jpg|png|svg)/i', $this->getHtmlUrl() ) ) {
                return "<img src=\"". $this->getHtmlUrl() ."\" />";
            }

            //albums, embeds other strange thigs
            if ( preg_match( '/embed$/i', $this->getHtmlUrl() ) ) {

                if ( function_exists( 'curl_init' ) ) {
                    $content = $this->file_get_contents_curl( $this->getHtmlUrl() );
                }else {
                    $content = @file_get_contents( $this->getHtmlUrl() );
                }

                return '<a href="'.$this->getHtmlUrl().'"><img src="' . preg_replace("/s\./", ".", $this->getImage($content)) . '"/></a>';
            }
            if ( $this->scrape ) {
 
                if ( $contentFromReadability = $this->fetchFromReadability( $this->getHtmlUrl() ) ) {
                    return $contentFromReadability;
                }
                if ( $contentFromInstapaper = $this->fetchFromInstapaper( $this->getHtmlUrl() ) ) {
                    return $contentFromInstapaper;
                }
            }
            return @current( $this->items )->data->url;
        }
        return false;
    }


    /**
     * returns the icon of this item
     *
     * @return string icon url
     */
    public function getIcon() {
        $imageHelper = $this->getImageHelper();
        $htmlUrl = $this->getHtmlUrl();
        if ( $htmlUrl && $imageHelper->fetchFavicon( $htmlUrl ) )
            $this->faviconUrl = $imageHelper->getFaviconUrl();
        return $this->faviconUrl;
    }


    /**
     * returns the link of this item
     *
     * @return string link
     */
    public function getLink() {
        if ( $this->items!==false && $this->valid() )
            return "http://reddit.com" . @current( $this->items )->data->permalink;
        return false;
    }

    /**
     * returns the thumbnail of this item
     *
     * @return string thumbnail url
     */
    public function getThumbnail() {
        if ( $this->items!==false && $this->valid() )
            return @current( $this->items )->data->thumbnail;
        return false;
    }
    
    /**
     * returns the date of this item
     *
     * @return string date
     */
    public function getdate() {
        if ( $this->items!==false && $this->valid() )
            $date = date( 'Y-m-d H:i:s', @current( $this->items )->data->created_utc );
        return $date;
    }


    /**
     * destroy the plugin (prevent memory issues)
     */
    public function destroy() {
        unset( $this->items );
        $this->items = false;
    }


    /**
     * returns the xml feed url for the source
     *
     * @return string url as xml
     * @param mixed $params params for the source
     */
    public function getXmlUrl($params) {
        return  "reddit://".urlencode($params['url']);
    }

    /**
     * fetch content from readability.com
     *
     * @author oxman @github
     * @return string content
     */
    private function fetchFromReadability( $url ) {
        if ( empty( $this->apiKey ) ) {
            return false;
        }
        if ( function_exists( 'curl_init' ) ) {
            $content = $this->file_get_contents_curl( "https://readability.com/api/content/v1/parser?token=" . $this->apiKey . "&url=" . urlencode( $url ) );
        }else {
            $content = @file_get_contents( "https://readability.com/api/content/v1/parser?token=" . $this->apiKey . "&url=" . urlencode( $url ) );
        }

        $data = json_decode( $content );
        if ( isset( $data->content )===false )
            return false;
        return $data->content;
    }

    /**
     * fetch content from instapaper.com
     *
     * @author janeczku @github
     * @return string content
     */
    private function fetchFromInstapaper( $url ) {
        if ( function_exists( 'curl_init' ) ) {
            $content = $this->file_get_contents_curl( "http://www.instapaper.com/text?u=" . urlencode( $url ) );
        }
        else {
            $content = @file_get_contents( "http://www.instapaper.com/text?u=" . urlencode( $url ) );
        }
        $dom = new \DOMDocument();
        @$dom->loadHTML( $content );
        if ( !$dom )
            return false;
        $xpath = new \DOMXPath( $dom );
        $elements = $xpath->query( "//div[@id='story']" );
        $content = $dom->saveXML( $elements->item( 0 ), LIBXML_NOEMPTYTAG );
        return $content;
    }

    private function file_get_contents_curl( $url ) {
        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 15 );
        curl_setopt( $ch, CURLOPT_TIMEOUT, 15 );
        if (!empty($this->reddit_session)) {
            curl_setopt($ch, CURLOPT_COOKIE, $this->reddit_session);
        }
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt( $ch, CURLOPT_URL, $url );
        $data = @curl_exec( $ch );
        curl_close( $ch );

        return $data;
    }

    private function get_head( $url ) {
        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 15 );
        curl_setopt( $ch, CURLOPT_TIMEOUT, 15 );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
        // Only calling the head
        if (!empty($this->reddit_session)) {
            curl_setopt($ch, CURLOPT_COOKIE, $this->reddit_session);
        }
        curl_setopt($ch, CURLOPT_HEADER, true); // header will be at output
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1); // ADD THIS

        curl_setopt( $ch, CURLOPT_URL, $url );
        $data = @curl_exec( $ch );
        curl_close( $ch );

        return $data;
    }

    /**
     * taken from: http://zytzagoo.net/blog/2008/01/23/extracting-images-from-html-using-regular-expressions/
     * Searches for the first occurence of an html <img> element in a string
     * and extracts the src if it finds it. Returns boolean false in case an
     * <img> element is not found.
     * @param    string  $str    An HTML string
     * @return   mixed           The contents of the src attribute in the
     *                           found <img> or boolean false if no <img>
     *                           is found
     */
    private function getImage($html) {
        if (stripos($html, '<img') !== false) {
            $imgsrc_regex = '#<\s*img [^\>]*src\s*=\s*(["\'])(.*?)\1#im';
            preg_match($imgsrc_regex, $html, $matches);
            unset($imgsrc_regex);
            unset($html);
            if (is_array($matches) && !empty($matches)) {
                return $matches[2];
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    private function login($params)
    {
        $login = sprintf("api_type=json&user=%s&passwd=%s", $params['username'], $params['password']);
        $ch = curl_init("https://ssl.reddit.com/api/login/{$params['username']}");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 15 );
        curl_setopt( $ch, CURLOPT_TIMEOUT, 15 );
        curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, "POST" );
        curl_setopt($ch, CURLOPT_POSTFIELDS, $login);
        $response = curl_exec($ch);
        $response = json_decode($response);
        if (curl_errno($ch)) {
            print(curl_error($ch));
        } else {
            curl_close($ch);
            if (count($response->json->errors) > 0){
                print($response);    
            } else {
                $this->reddit_session = "reddit_session={$response->json->data->cookie}";
                if (function_exists("apc_store")) {
                    apc_store("{$params['username']}_slefoss_reddit_session", $this->reddit_session, 3600);
                }
            }
        }
    }
}
