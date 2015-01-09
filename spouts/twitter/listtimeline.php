<?php

namespace spouts\twitter;

/**
 * Spout for fetching a twitter list
 *
 * @package    spouts
 * @subpackage twitter
 * @copyright  Copyright (c) Nicola Malizia (http://unnikked.ga)
 * @license    GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Nicola Malizia <unnikked@gmail.com>
 */

class listtimeline extends \spouts\twitter\usertimeline {


    public function __construct() {
    
        $this->name = 'Twitter - List timeline';
        $this->description = 'The timeline of a given list';
        $this->params = array(
            "consumer_key" => array(
                "title"      => "Consumer Key",
                "type"       => "text",
                "default"    => "",
                "required"   => true,
                "validation" => array("notempty")
            ),
            "consumer_secret" => array(
                "title"      => "Consumer Secret",
                "type"       => "password",
                "default"    => "",
                "required"   => true,
                "validation" => array("notempty")
            ),
            "slug" => array(
                "title"      => "List Slug",
                "type"       => "text",
                "default"    => "",
                "required"   => true,
                "validation" => array("notempty")
            ),
            "owner_screen_name" => array(
                "title"      => "Username",
                "type"       => "text",
                "default"    => "",
                "required"   => true,
                "validation" => array("notempty")
            )
        );
    }

    /**
     * loads content for given list
     *
     * @return void
     * @param mixed $params the params of this source
     */
    public function load($params) {
        $twitter = new \TwitterOAuth($params['consumer_key'], $params['consumer_secret']);
        $timeline = $twitter->get('lists/statuses', 
                            array('slug' => $params['slug'], 
                                  'owner_screen_name' => $params['owner_screen_name'], 
                                  'include_rts' => 1, 
                                  'count' => 50));
        
        if(isset($timeline->error))
            throw new \exception($timeline->error);
        
        if(!is_array($timeline))
            throw new \exception('invalid twitter response');
        
        $this->items = $timeline;
        
        $this->htmlUrl = 'http://twitter.com/' . urlencode($params['owner_screen_name']);
    }

}
