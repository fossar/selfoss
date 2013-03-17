<?PHP

namespace helpers;

define("DEBUG", 5);
define("INFO", 4);
define("NOTICE", 3);
define("WARNING", 2);
define("ERROR", 1);
define("NONE", 0);

/**
 * Logger
 *
 * @package    helpers
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
class Logger {
    
    /**
     * target file
     * @var string
     */
    private $target;
    
    
    /**
     * max severity level
     * @var int
     */
    private $severityLimit;
    
    
    /**
     * textual representation of severity levels
     * @var array
     */
    private $severityText = array(
        DEBUG   => "Debug",
        INFO    => "Info",
        NOTICE  => "Notice",
        WARNING => "Warning",
        ERROR   => "Error",
        NONE    => "None"
    );
    
    
    /**
     * set logger config
     *
     * @param string $target logfile
     * @param int $severity log level
     */
    public function __construct($target, $severity = ERROR) {
        if(is_string($severity)) {
            $text2severity = array_flip(array_map("strtoupper", $this->severityText));
            $severity = $text2severity[strtoupper($severity)];
        }
        
        $this->target = $target;
        $this->severityLimit = $severity;
    }
    
    
    /**
     * write log message
     *
     * @return void
     * @param string $message
     * @param int $severity
     */
    public function log($message, $severity) {
        if($severity > $this->severityLimit)
            return;
            
        $msg = date("m-d-y") . " " . 
               date("G:i:s") . " " . 
               $this->severityText[$severity] . " " . 
               $message . "\n";
        
        $fileHandle = fopen($this->target, "at+");
        if($fileHandle===false)
            return;
        
        fwrite($fileHandle, $msg);
        fclose($fileHandle);
    }
}
