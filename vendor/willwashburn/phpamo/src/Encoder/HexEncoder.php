<?php namespace WillWashburn\Phpamo\Encoder;

/**
 * Class HexEncoder
 * @package WillWashburn\Phpamo\Encoder
 */
class HexEncoder implements EncoderInterface
{
    /**
     * @param $url
     *
     * @return mixed
     */
    public function encode($url)
    {
        return bin2hex($url);
    }
}