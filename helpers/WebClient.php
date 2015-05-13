<?PHP
namespace helpers;

/**
 * Helper class for web request
 *
 * @package    helpers
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Alexandre Rossi <alexandre.rossi@gmail.com>
 */
class WebClient {
    /**
     * get the user agent to use for web based spouts
     *
     * @return the user agent string for this spout
     */
    public static function getUserAgent($agentInfo=null){
        $userAgent = 'Selfoss/'.\F3::get('version');

        if( is_null($agentInfo) )
            $agentInfo = array();

        $agentInfo[] = '+http://selfoss.aditu.de';

        return $userAgent.' ('.implode('; ', $agentInfo).')';
    }
    
    
    /**
     * Retrieve content from url
     *
     * @param string $subagent Extra user agent info to use in the request
     * @return request data
     */
    public static function request($url, $agentInfo=null) {
        $options  = array(
            'user_agent' => self::getUserAgent($agentInfo),
            'ignore_errors' => true,
            'timeout' => 60
        );
        $request = \Web::instance()->request($url, $options);

        // parse last (in case of redirects) HTTP status
        $http_status = null;
        foreach( $request['headers'] as $header ) {
            if( substr($header, 0, 5) == 'HTTP/' ) {
                $tokens = explode(' ', $header);
                if( isset($tokens[1]) )
                    $http_status = $tokens[1];
            }
        }

        if( $http_status != '200' ) {
            throw new \exception(substr($request['body'], 0, 512));
        }
        return $request['body'];
    }
}
