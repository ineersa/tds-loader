<?php

namespace Ineersa\TDS;

class Loader
{
    private $url = 'test';

    private $hash;

    private $timeout = 20;

    private $debug = false;

    private $advDebug = false; // Note that enabling advanced debug will include debugging information in the response possibly breaking up your code

    private $log = false;

    public $responseCode;

    private const METHOD_POST = 'POST';

    private const METHOD_GET = 'GET';

    public function __construct($hash)
    {
        $this->hash = $hash;
    }

    public function getContent()
    {
        $data = [
            'key' => $this->hash,
            'ip' => $this->resolveIp(),
            'user_agent' => $_SERVER["HTTP_USER_AGENT"] ?? '',
            'user_locale' => $this->resolveLocale(),
            'referer' => $_SERVER['HTTP_REFERER'] ?? '',
            'utm_term' => $_REQUEST['utm_term'],
        ];

        return $data;
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
        if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])){
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
        curl_setopt($curl, CURLOPT_FAILONERROR, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); // Don't verify SSL connection
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0); //         ""           ""
        if ($this->advDebug) {
            curl_setopt($curl, CURLOPT_HEADER, true); // Display headers
            curl_setopt($curl, CURLOPT_VERBOSE, true); // Display communication with server
        }
        if ($method == self::METHOD_POST) {
            curl_setopt($curl, CURLOPT_POST, true);
        }
        if (!is_null($data) && ($method == self::METHOD_POST)) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }

        try {
            $return = curl_exec($curl);
            $this->responseCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            if ($this->debug || $this->advDebug) {
                echo "<pre>";
                print_r(curl_getinfo($curl));
                echo "</pre>";
                echo "<pre>";
                print_r($data);
                echo "</pre>";
            }
        } catch (\Exception $ex) {
            if ($this->debug || $this->advDebug) {
                echo "<br>cURL error num: " . curl_errno($curl);
                echo "<br>cURL error: " . curl_error($curl);
            }
            echo "Error on cURL";

            $return = null;
        }

        curl_close($curl);

        return $return;
    }
}