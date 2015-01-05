<?PHP

namespace helpers;

/**
 * Helper class for getting real URL from feed
 * as well as removing tracking params
 *
 * @package    helpers
 * @copyright  Copyright (c) Jean Baptiste Favre (http://www.jbfavre.org)
 * @license    GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Jean Baptiste Favre <selfoss@jbfavre.org>
 */
class UrlCleaner {
    
    /**
     * url of last fetched item
     * @var string
     */
    private $realUrl = false;


    /**
     * get real url if specific patterns are found in host
     *
     * @return string $url
     * @param string $url
     */
    public function processUrl($url) {
	$urlToken = parse_url($url);
	$patterns = ['feeds', 'rss'];
        foreach ( $patterns as $pattern ) {
            if ( strpos( $urlToken['host'], $pattern) !== false ) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL,$url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
                curl_setopt($ch, CURLOPT_HEADER, true);
                curl_exec($ch);
                $url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
                break;
            }
        }
        $this->realUrl = $this->cleanTrackers($url);
    }

    /**
     * get favicon url
     *
     * @return string
     */
    public function getRealUrl() {
        return $this->realUrl;
    }

    /**
     * remove trakers from url
     *
     * @return string url
     */
    private function cleanTrackers() {
        $url = parse_url($this->realUrl);
        // Next, rebuild URL
        $realUrl = $url['scheme'] . '://';
        if (isset($url['user']) && isset($url['password']))
            $realUrl .= $url['user'] . ':' . $url['password'] . '@';
        $realUrl .= $url['host'] . $url['path'];
        // Query string
        if (isset($url['query'])) {
            parse_str($url['query'], $q_array);
            $realQuery = array();
            foreach ($q_array as $key => $value) {
                // Remove utm_* &xtor parameters
                if(strpos($key, 'utm_')===false && strpos($key, 'xtor')===false)
                    $realQuery[]= $key.'='.$value;
            }
            if (count($real_query))
                $realUrl .= '?' . implode('&', $realQuery);
        }
        // Fragment
        if (isset($url['fragment'])) {
            // Remove xtor=RSS anchor
            if (strpos($url['fragment'], 'xtor')===false)
                $realUrl .= '#' . $url['fragment'];
        }
        $this->realUrl = $realUrl;
    }

}
