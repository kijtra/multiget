<?php
namespace Kijtra\Multiget;

use \Kijtra\Multiget\Config;

class Curl
{
    use Config;

    private $errors = array();

    private function getCached(&$stacks)
    {
        if (empty($stacks) || empty(Config::$storagePath)) {
            return array();
        }

        $mh = curl_multi_init();
        $handles = array();
        foreach ($stacks as $stack) {
            if (!$stack->isFile()) {
                continue;
            }

            $ch = curl_init($stack->url('url'));
            curl_setopt_array($ch, Config::$optionsGetHeader);
            curl_multi_add_handle($mh, $ch);

            $handles[$stack->url('hash')] = $ch;
        }

        if (empty($handles)) {
            curl_multi_close($mh);
            return;
        }

        $running = null;
        do {
            curl_multi_exec($mh, $running);
        } while($running > 0);

        $cached = array();

        foreach($handles as $hash => $ch) {
            $info = curl_getinfo($ch);
            curl_multi_remove_handle($mh, $ch);

            if (!preg_match('/\A[23]/', $info['http_code'])) {
                $stacks[$hash]->setError($info);
                continue;
            }

            $header = curl_multi_getcontent($ch);
            if (preg_match('/Last-Modified: ?([^\r\n]+)/i', $header, $match)) {
                $filetime = strtotime($match[1]);
                // vd(array($stacks[$hash]->url(), filemtime($stacks[$hash]->getFile()),$filetime));
            } else {
                $filetime = curl_getinfo($ch, CURLINFO_FILETIME);
            }

            $file = $stacks[$hash]->getFile();

            if (filemtime($file) == $filetime) {
                $cached[$hash] = true;
            }
        }

        curl_multi_close($mh);

        return $cached;
    }

    public function save(&$stacks)
    {
        $storage = Config::$storagePath;
        $cached = $this->getCached($stacks);

        $datas = array();

        $mh = curl_multi_init();
        $handles = array();
        foreach ($stacks as $hash => $stack) {
            if (!empty($cached[$hash])) {
                $stacks[$hash]->setCached(true);
                continue;
            }

            $ch = curl_init($stack->url('url'));

            $options = Config::$optionsGetContent;
            $options[CURLOPT_FILE] = fopen($stack->getFile(), 'w');

            curl_setopt_array($ch, $options);
            curl_multi_add_handle($mh, $ch);

            $handles[$hash] = $ch;
        }

        if (!empty($handles)) {
            $running = null;
            do {
                curl_multi_exec($mh, $running);
            } while($running > 0);

            foreach($handles as $hash => $ch) {
                $info = curl_getinfo($ch);
                curl_multi_remove_handle($mh, $ch);

                if (!preg_match('/\A[23]/', $info['http_code'])) {
                    $stacks[$hash]->setError($info);
                }

                $filetime = $info['filetime'];
                $length = $info['download_content_length'];

                touch($stacks[$hash]->getFile(), $filetime, time());
            }

            curl_multi_close($mh);
        }

        return $stacks;
    }
}
