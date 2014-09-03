<?PHP
if (!class_exists('FeedWriter'))
	require dirname(__FILE__) . '/FeedWriter.php';
    
/**
 * Wrapper for creating RSS2 feeds
 *
 * @package     UniversalFeedWriter
 */
class RSS2FeedWriter extends FeedWriter
{
	function __construct()
	{
		parent::__construct(RSS2);
	}
}