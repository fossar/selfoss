<?php namespace WillWashburn\Phpamo\Encoder;

/**
 * Encodes for the QueryString implementation of camo
 *
 * @package WillWashburn\Phpamo\Encoder
 */
class QueryStringEncoder implements EncoderInterface
{

    /**
     * @param $url
     *
     * @return mixed
     */
    public function encode($url)
    {
        return rawurlencode($url);
    }
}