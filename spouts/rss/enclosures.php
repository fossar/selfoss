<?PHP 

namespace spouts\rss;

/**
 * Plugin for fetching RSS feeds with image enclosures
 *
 * @package    plugins
 * @subpackage news
 * @copyright  Copyright (c) Daniel Rudolf
 * @license    GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Daniel Rudolf <http://daniel-rudolf.de/>
 */
class enclosures extends feed
{
    /**
     * name of spout
     *
     * @var string
     */
    public $name = 'RSS Feed (with enclosures)';


    /**
     * description of this source type
     *
     * @var string
     */
    public $description = 'This feed type adds image enclosures to the feed content';


    /**
     * returns the content of this item
     *
     * @return string content
     */
    public function getContent() {
        if($this->items!==false && $this->valid()) {
            $content = parent::getContent();
            foreach(@current($this->items)->get_enclosures() as $enclosure) {
                if($enclosure->get_medium()=='image') {
                    $title = htmlspecialchars(strip_tags($enclosure->get_title()));
                    $content .= '<img src="'.$enclosure->get_link().'" alt="'.$title.'" title="'.$title.'" />';
                }
            }
            return $content;
        }
        return parent::getContent();
    }

}
