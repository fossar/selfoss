<?php namespace WillWashburn\Phpamo\Formatter;

/**
 * Interface FormatterInterface
 *
 * @package WillWashburn\Phpamo\Formatter
 */
interface FormatterInterface
{

    /**
     * @param $domain
     * @param $digest
     * @param $url
     *
     * @return mixed
     */
    public function formatCamoUrl($domain, $digest, $url);
}