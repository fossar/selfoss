<?php namespace WillWashburn\Phpamo\Encoder;

/**
 * EncoderInterface
 *
 * @package WillWashburn\Phpamo\Encoder
 */
interface EncoderInterface {

    /**
     * @param $url
     *
     * @return mixed
     */
    public function encode($url);

}