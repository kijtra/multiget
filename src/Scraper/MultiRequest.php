<?php
namespace Kijtra\Scraper;

class MultiRequest
{
    private $optionsGetHeader = array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HEADER => true,
        CURLOPT_NOBODY => true,
        CURLOPT_FILETIME => true,
        CURLOPT_FORBID_REUSE => true,
        CURLOPT_FRESH_CONNECT => true,
        CURLOPT_TIMEOUT => 10
    );

    private $optionsGetContent = array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HEADER => false,
        CURLOPT_FILETIME => true,
        CURLOPT_FORBID_REUSE => true,
        CURLOPT_FRESH_CONNECT => true,
        CURLOPT_TIMEOUT => 10
    );

    private $logger;
    private $errors;

    public function __construct($logger = null, $errors = null)
    {
        if (!empty($logger) && $logger instanceof Logger) {
            $this->logger = $logger;
        }

        if (!empty($errors) && $errors instanceof Errors) {
            $this->errors = $errors;
        }
    }

    public function setTimeout($seconts)
    {
        $optionsGetHeader[CURLOPT_TIMEOUT] = $seconts;
        $optionsGetContent[CURLOPT_TIMEOUT] = $seconts;
    }

    public function setUserAgent($ua)
    {
        $optionsGetHeader[CURLOPT_USERAGENT] = $ua;
        $optionsGetContent[CURLOPT_USERAGENT] = $ua;
    }

    public function getContents(array $urls)
    {
        if (empty($urls)) {
            return array();
        }

        if (!empty($this->logger)) {
            $headers = $this->getHeaders($urls);
            foreach($headers as $urlKey => $header) {
                if ($this->logger->isNotModified($urlKey, $header['length'], $header['date'])) {
                    unset($urls[$urlKey]);
                }
            }
        }

        $contents = array();
        $this->doRequest($urls, $this->optionsGetContent, function($urlKey, $url, $ch) use(&$contents) {
            $content = curl_multi_getcontent($ch);
            $contents[$url] = $content;
        });

        return $contents;
    }

    private function getHeaders(array $urls)
    {
        if (empty($urls)) {
            return;
        }

        $headers = array();
        $this->doRequest($urls, $this->optionsGetHeader, function($urlKey, $url, $ch) use(&$headers) {
            $time = curl_getinfo($ch, CURLINFO_FILETIME);
            $length = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
            if ($length <= 0) {
                $res = curl_multi_getcontent($ch);
                if (preg_match('/Content-Length[^\d]+(\d+)/i', $res, $match)) {
                    $length = $match[1];
                }
            }
            $header = array(
                'length' => (int)$length,
                'date' => ($time > 0 ? date('Y-m-d H:i:s', $time) : null)
            );
            $headers[$urlKey] = $header;
        });

        return $headers;
    }

    private function doRequest(array $urls, array $options, \Closure $callback)
    {
        $mh = curl_multi_init();

        $chs = array();
        foreach ($urls as $urlKey => $url) {
            $ch = curl_init($url);
            curl_setopt_array($ch, $options);
            curl_multi_add_handle($mh, $ch);
            $chs[$urlKey] = $ch;
        }

        $running = null;
        do {
            curl_multi_exec($mh, $running);
        } while($running > 0);

        foreach($chs as $urlKey => $ch) {
            $url = $urls[$urlKey];
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if (!preg_match('/\A[23]/', $code)) {
                if (!empty($this->errors)) {
                    $this->errors->add(new \Exception('"'.$code.'" Error at '.$url.'.'));
                }
                continue;
            }

            $callback($urlKey, $url, $ch);
            curl_multi_remove_handle($mh, $ch);
        }

        curl_multi_close($mh);
    }
}
