<?PHP

namespace helpers;

/**
 * Helper class for getting real URL from feed
 * as well as removing tracking params from
 * query string or fragments
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
     * patterns to look for in URL host
     * @var array
     */
    private $hostPatterns = ['feed', 'rss', 't.co'];

    /**
     * patterns to look for in URL query string
     * @var array
     */
    private $queryPatterns = ['utm_', 'xtor'];

    /**
     * patterns to look for in URL fragment
     * @var array
     */
    private $fragmentPatterns = ['xtor', 'ens_id'];


    /**
     * get real url if specific patterns are found in host
     *
     * @return string $url
     * @param string $url
     */
    public function processUrl($url) {
	$this->realUrl = $url;
	$urlToken = parse_url($url);

	// Look for host patterns. If found, use curl to get real URL
        foreach ( $this->hostPatterns as $pattern ) {
	    if ( strpos($urlToken['host'], $pattern) !== false ) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
		curl_setopt($ch, CURLOPT_HEADER, TRUE);
		curl_exec($ch);
		$this->realUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
		unset($ch);
		break;
	    }
	}
	$this->cleanTrackers();
	return true;
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

	// Start rebuilding URL
        $realUrl = $url['scheme'] . '://';
        if (isset($url['user']) && isset($url['password']))
            $realUrl .= $url['user'] . ':' . $url['password'] . '@';
        $realUrl .= $url['host'] . $url['path'];

        // Query string
        if (isset($url['query'])) {
            parse_str($url['query'], $q_array);
            $realQuery = array();
            foreach ($q_array as $key => $value) {
                // Remove trackers from URL query string
		foreach ( $this->queryPatterns as $pattern ) {
		    if ( strpos($key, $pattern) !== false )
			$realQuery[]= $key.'='.$value;
		}
            }
	    $realQuery = http_build_query($q_array);
            if ( $realQuery )
                $realUrl .= '?' . $realQuery;
	    unset($q_array);
	    unset($realQuery);
        }

        // Fragment
        if (isset($url['fragment'])) {
	    // Remove trackers from URL fragment
	    foreach ( $this->fragmentPatterns as $pattern ) {
		if ( strpos($url['fragment'], 'xtor') === false )
		    $realUrl .= '#' . $url['fragment'];
	    }
        }
        $this->realUrl = $realUrl;
	unset($url);
    }

}
