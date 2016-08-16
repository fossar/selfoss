<?php namespace WillWashburn\Phpamo;

use Exception;
use WillWashburn\Phpamo\Encoder\HexEncoder;
use WillWashburn\Phpamo\Formatter\FormatterInterface;
use WillWashburn\Phpamo\Formatter\HexFormatter;

/**
 * Fah-ham-o
 * An exercise on going ham-o with camo.
 *
 * For more information about setting up Camo, please see
 * https://github.com/atmos/camo
 *
 * @package WillWashburn\Phpamo
 */
class Phpamo
{
    private $domain;
    private $key;
    private $formatter;

    /**
     * You're only required to pass in the key and domain for the basic setup
     *
     * @param                    $key
     * @param                    $domain
     * @param FormatterInterface $formatter
     */
    public function __construct(
        $key,
        $domain,
        FormatterInterface $formatter = null
    )
    {
        $this->key    = $key;
        $this->domain = $domain;

        $this->runSanityChecks();

        if ( is_null($formatter) ) {
            $formatter = new HexFormatter(new HexEncoder());
        }

        $this->formatter = $formatter;
    }

    /**
     * Camoflauge all urls
     *
     * @param $url
     *
     * @return string
     */
    public function camo($url)
    {
        return $this->formatter->formatCamoUrl(
            $this->domain,
            $this->getDigest($url),
            $url
        );
    }

    /**
     * Camoflauge only the urls that are not currently https
     *
     * @param $url
     *
     * @return mixed
     */
    public function camoHttpOnly($url)
    {

        $parts = parse_url($url);

        if ( isset($parts['scheme']) && $parts['scheme'] == 'https' ) {
            return $url;
        }

        return $this->camo($url);
    }

    /**
     * @param $url string
     *
     * @return string
     */
    protected function getDigest($url)
    {
        return hash_hmac('sha1', $url, $this->key);
    }

    /**
     * @throws \Exception
     */
    private function runSanityChecks()
    {
        if ( empty($this->key) ) {
            throw new Exception('You need to supply a key to Phpamo');
        }

        if ( empty($this->domain) ) {
            throw new Exception('You need to add a domain to Phpamo');
        }
    }

}
