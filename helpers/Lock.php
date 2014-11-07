<?PHP

namespace helpers;

/**
 * Helper class for mutexes
 *
 * @package    helpers
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Alexandre Rossi <alexandre.rossi@gmail.com>
 */
class Lock {

    private $locks_dir = '../data/locks/';


    /**
     * ctor
     */
    public function __construct($name) {
        $this->name = $name;
        $this->path = __DIR__ . '/' . $this->locks_dir . $this->name;
        $this->fp = null;
    }


    /**
     * Acquire an exclusive lock.
     *
     * @return boolean lock has been acquired
     */
    public function acquire() {
        $this->fp = fopen($this->path, 'a');
        return flock($this->fp, LOCK_EX | LOCK_NB);
    }


    /**
     * Release the lock
     *
     * @return boolean lock has been released
     */
    public function release() {
        $released = flock($this->fp, LOCK_UN);
        fclose($this->fp);
        return $released;
    }
}

?>
