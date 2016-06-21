<?php namespace WillWashburn\Phpamo\Formatter;

use WillWashburn\Phpamo\Encoder\QueryStringEncoder;

/**
 * Class QueryStringFormatter
 *
 * @package WillWashburn\Phpamo\Formatter
 */
class QueryStringFormatter implements FormatterInterface
{
    /**
     * @var QueryStringEncoder
     */
    private $encoder;

    /**
     * QueryStringFormatter constructor.
     *
     * @param QueryStringEncoder $encoder
     */
    public function __construct(QueryStringEncoder $encoder)
    {
        $this->encoder = $encoder;
    }

    /**
     * @param $domain
     * @param $digest
     * @param $url
     *
     * @return mixed
     */
    public function formatCamoUrl($domain, $digest, $url)
    {
        return 'https://' . $domain . '/' . $digest . '?url=' . $this->encoder->encode($url);
    }
}