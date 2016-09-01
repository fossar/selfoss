<?php namespace WillWashburn\Phpamo\Formatter;

use WillWashburn\Phpamo\Encoder\HexEncoder;

/**
 * Class HexFormatter
 *
 * @package WillWashburn\Phpamo\Formatter
 */
class HexFormatter implements FormatterInterface
{
    /**
     * @var HexEncoder
     */
    private $encoder;

    /**
     * HexFormatter constructor.
     *
     * @param HexEncoder $encoder
     */
    public function __construct(HexEncoder $encoder)
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
        return 'https://' . $domain . '/' . $digest . '/' . $this->encoder->encode($url).'/';
    }
}