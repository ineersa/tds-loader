<?php

namespace Ineersa\TDS;

class Loader
{
    private $url = '';

    private $hash;

    private $timeout = 20;

    private $debug = false;

    public $responseCode;

    private const METHOD_POST = 'POST';

    private const METHOD_GET = 'GET';

    /**
     * Loader constructor.
     * @param string $hash
     * @param string $url
     */
    public function __construct($hash, $url)
    {
        $this->hash = $hash;
        $this->url = $url;
    }

    /**
     * @return string
     */
    public function getContent()
    {
        $data = [
            'key' => $this->hash,
            'ip' => $this->resolveIp(),
            'user_agent' => $_SERVER["HTTP_USER_AGENT"] ?? '',
            'user_locale' => $this->resolveLocale(),
            'referer' => $_SERVER['HTTP_REFERER'] ?? '',
            'utm_term' => $_REQUEST['utm_term'] ?? '',
        ];

        $result = $this->ask($this->url, $data);

        if (empty($result) || !$result) {
            return '';
        }

        $response = json_decode($result, true);

        if ($response['status'] !== 'OK') {
            return $response['default_html'];
        }

        switch ($response['method']) {
            case 'redirect':
                header('Location: '.$response['link'], true, 302);
                exit(0);
            case 'curl':
            default:
                $content = $this->ask($response['link'], self::METHOD_GET);
                return !empty($content) ? $content : '';
        }
    }

    private function resolveLocale()
    {
        $header = $_SERVER["HTTP_ACCEPT_LANGUAGE"] ?? '';
        if (empty($header)) {
            return '';
        }

        return substr($header, 0, 6);
    }

    private function resolveIp()
    {
        //Check to see if the CF-Connecting-IP header exists.
        if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
            //If it does, assume that PHP app is behind Cloudflare.
            $ipAddress = $_SERVER["HTTP_CF_CONNECTING_IP"];
        } else {
            //Otherwise, use REMOTE_ADDR.
            $ipAddress = $_SERVER['REMOTE_ADDR'];
        }

        return $ipAddress ?? '';
    }

    /**
     * This function communicates with API.
     * You don't need to call this function directly. It's only for inner class working.
     *
     * @param string $url
     * @param array $data
     * @param int $method See constants defined at the beginning of the class
     * @return mixed CURL response
     */
    private function ask($url, $data = null, $method = self::METHOD_POST)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); // Don't print the result
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $this->timeout);
        curl_setopt($curl, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($curl, CURLOPT_FAILONERROR, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); // Don't verify SSL connection
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0); //         ""           ""

        if ($method == self::METHOD_POST) {
            curl_setopt($curl, CURLOPT_POST, true);
        }
        if (!is_null($data) && ($method == self::METHOD_POST)) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }

        try {
            $return = curl_exec($curl);
            if ($this->debug) {
                print "<pre>";
                print_r($return);
                print "</pre>";
            }
            $this->responseCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        } catch (\Exception $ex) {
            $return = null;
        }

        curl_close($curl);

        return $return;
    }

    /**
     * @return bool
     */
    public function isDebug(): bool
    {
        return $this->debug;
    }

    /**
     * @param bool $debug
     */
    public function setDebug(bool $debug): void
    {
        $this->debug = $debug;
    }

}