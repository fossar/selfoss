<?PHP

namespace helpers;

/**
 * Helper class for authenticate user
 *
 * @package    helpers
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
class Authentication {

    /**
     * loggedin
     * @var bool
     */
    private $loggedin = false;

    /**
     * enabled
     * @var bool
     */
    private $enabled = false;

    /**
     * start session and check login
     */
    public function __construct() {
        // session cookie will be valid for one month
        session_set_cookie_params((3600 * 24 * 30), "/");

        session_name();
        if (session_id() == "")
            session_start();
        if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
            $this->loggedin = true;
        } else if (isset($_COOKIE['rememberMe']) && $_COOKIE['rememberMe'] == hash("sha512", \F3::get('salt') . \F3::get('password') . \F3::get('salt') . \F3::get('username'))) {
            $this->loggedin = true;
        }

        $this->enabled = strlen(trim(\F3::get('username'))) != 0 && strlen(trim(\F3::get('password'))) != 0;

        // autologin if request contains unsername and password
        if ($this->enabled === true && $this->loggedin === false && isset($_REQUEST['username']) && isset($_REQUEST['password'])) {
            $this->login($_REQUEST['username'], $_REQUEST['password'], isset($_REQUEST['rememberMe']));
        }
    }

    /**
     * login enabled
     *
     * @return bool
     * @param string $username
     * @param string $password
     */
    public function enabled() {
        return $this->enabled;
    }

    /**
     * login user
     *
     * @return bool
     * @param string $username
     * @param string $password
     */
    public function loginWithoutUser() {
        $this->loggedin = true;
    }

    /**
     * login user
     *
     * @return bool
     * @param string $username
     * @param string $password
     */
    public function login($username, $password, $rememberMe = false) {
        if ($this->enabled()) {
            if (
                    $username == \F3::get('username') && hash("sha512", \F3::get('salt') . $password) == \F3::get('password')
            ) {
                if(isset($rememberMe) && $rememberMe === true) {
                    $this->set_cookie('rememberMe', hash("sha512", \F3::get('salt') . \F3::get('password') . \F3::get('salt') . \F3::get('username')));
                }
                $this->loggedin = true;
                $_SESSION['loggedin'] = true;
                return true;
            }
        }
        return false;
    }

    /**
     * isloggedin
     *
     * @return bool
     */
    public function isLoggedin() {
        if ($this->enabled() === false)
            return true;
        return $this->loggedin;
    }

    /**
     * logout
     *
     * @return void
     */
    public function logout() {
        $this->loggedin = false;
        $_SESSION['loggedin'] = false;
        $this->set_cookie('rememberMe', '');
        session_destroy();
    }

    public function set_cookie($name, $value = '') {
        if ($value == '')
            $expires = time() - 6000;
        else
            $expires = time() + 3600 * 24 * 360;
        
        setcookie($name, $value, $expires);
    }

}