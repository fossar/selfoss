<?PHP 

namespace spouts\rss;

/**
 * Plugin for fetching media from the ZDF Mediathek
 *
 * @package    plugins
 * @subpackage news
 * @copyright  Copyright (c) Daniel Rudolf
 * @license    GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Daniel Rudolf <http://daniel-rudolf.de/>
 */
class zdf extends feed
{
    /**
     * name of spout
     *
     * @var string
     */
    public $name = 'ZDF Mediathek';


    /**
     * description of this source type
     *
     * @var string
     */
    public $description = 'This feed fetches media items from the ZDF Mediathek.';

    /**
     * loads the contents of the feed
     *
     * @param mixed $params the params of this source
     * @return void
     */
    public function load($params)
    {
        parent::load($params);

        foreach ($this->items as $key => $item) {
            // remove future items
            $time = $item->get_date('U');
            if ($time && ($time > time())) {
                unset($this->items[$key]);
            }
        }
    }

}
