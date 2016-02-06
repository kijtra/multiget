<?php
namespace Kijtra;

use Kijtra\Multiget\Config;
use Kijtra\Multiget\Stack;
use Kijtra\Multiget\Url;
use Kijtra\Multiget\Curl;

class Multiget
{
    use Config;

    private $stacks = array();
    private $clean = false;

    public function __construct($storage = null)
    {
        Config::setStorage($storage);
    }

    public function clean($flag = true)
    {
        $this->clean = (is_bool($flag) ? $flag : true);
        return $this;
    }

    public function url($url)
    {
        $url = new Url($url);
        return $this->urls[$url->hash] = $url;
    }

    public function run($callback = null)
    {
        $mh = curl_multi_init();
        $handles = array();
        foreach ($this->urls as $hash => $url) {
            if ($this->clean) {
                unlink($url->file);
            } elseif (Config::$storagePath && is_file($url->file)) {
                continue;
            }
            $ch = curl_init($url->url);
            $options = Config::$curlOptions;
            $options[CURLOPT_FILE] = fopen($url->file, 'w');
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
                $url = $this->urls[$hash];
                $info = curl_getinfo($ch);
                curl_multi_remove_handle($mh, $ch);
                $this->urls[$hash]->info = $info;

                if (!preg_match('/\A[23]/', $info['http_code'])) {
                    $this->urls[$hash]->isError = true;
                }
            }
        }

        curl_multi_close($mh);

        $results = array();
        foreach($this->generator() as $result) {
            $results[] = $result;
        }

        if ($callback instanceof \Closure) {
            $callback($results);
        }
    }

    private function generator()
    {
        foreach($this->urls as $url) {
            $result = $callback = null;
            if ($url->isError) {
                if (!empty($url->error)) {
                    $callback = $url->error;
                }
            } elseif(!empty($url->success)) {
                $callback = $url->success;
            }

            if (!empty($callback)) {
                $url->info += array(
                    'url' => $url->url,
                    'raw_url' => $url->raw,
                    'file' => $url->file,
                    'length' => filesize($url->file),
                );
                $array = new \ArrayObject($url->info, \ArrayObject::ARRAY_AS_PROPS);
                $callback = $callback->bindTo($array);
                $result = $callback($url->file);
            }

            yield $result;
        }
    }
}
