<?PHP

namespace spouts\youtube;

/**
 * Spout for fetching an Youtube rss feed
 *
 * @package    spouts
 * @subpackage rss
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 * @copywork   Arndt Staudinger <info@clucose.com> April 2013
 */
class youtube extends \spouts\rss\feed {

    /**
     * name of source
     *
     * @var string
     */
    public $name = 'Youtube RSS Feed';

    /**
     * description of this source type
     *
     * @var string
     */
    public $description = 'An Youtube RSS Feed as source';

    /**
     * config params
     * array of arrays with name, type, default value, required, validation type
     *
     * - Values for type: text, password, checkbox
     * - Values for validation: alpha, email, numeric, int, alnum, notempty
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
        "channel" => array(
            "title"      => "channel",
            "type"       => "text",
            "default"    => "",
            "required"   => true,
            "validation" => array("notempty")
        )
    );

    /**
     * loads content for given source
     * I supress all Warnings of SimplePie for ensuring
     * working plugin in PHP Strict mode
     *
     * @return void
     * @param mixed $params the params of this source
     */
    public function load($params) {
        parent::load(array( 'url' => $this->getXmlUrl($params)) );
    }


    /**
     * returns the xml feed url for the source
     *
     * @return string url as xml
     * @param mixed $params params for the source
     */
    public function getXmlUrl($params) {
        return "http://gdata.youtube.com/feeds/api/users/" . $params['channel'] . "/uploads?alt=rss&orderby=published";
    }


    /**
     * returns the date of this item
     *
     * @return string date
     */
    public function getDate() {
        if($this->items!==false && $this->valid()){
            $date1 = @current($this->items)->get_item_tags('', 'pubDate');
            $date = date('Y-m-d H:i:s', strtotime($date1[0]['data']));
        } 
        if(strlen($date)==0)
            $date = date('Y-m-d H:i:s');
        return $date;         
    }


    /**
     * returns the thumbnail of this item (for multimedia feeds)
     *
     * @return mixed thumbnail data
     */
    public function getThumbnail() {
        if($this->items===false || $this->valid()===false)
            return "";

        $item = current($this->items);

        // search enclosures (media tags)
        if(count(@$item->get_enclosures()) > 0) {

            // thumbnail given?
            if(@$item->get_enclosure(0)->get_thumbnail())
                return @$item->get_enclosure(0)->get_thumbnail();

            // link given?
            elseif(@$item->get_enclosure(0)->get_link())
                return @$item->get_enclosure(0)->get_link();

        // no enclosures: search image link in content
        } else {

            $image = $this->getImage(@$item->get_content());  
            if($image!==false)
                return $image;
        }

        return "";
    }
}
