<?php
namespace Kijtra;

use Kijtra\Multiget\Config as MultigetConfig;
use Kijtra\Multiget\Url as MultigetUrl;

class Multiget
{
    private $stacks = array();
    private $clean = false;

    public function __construct($storage = null, $cache = null)
    {
        MultigetConfig::setStorage($storage);
        $this->cache($cache);
    }

    public function cache($seconds = null)
    {
        if (ctype_digit(strval($seconds))) {
            MultigetConfig::setCacheSecond((int)$seconds);
        }
        return $this;
    }

    public function url($url)
    {
        $url = new MultigetUrl($url);
        return $this->urls[$url->hash] = $url;
    }

    public function run($callback = null)
    {
        $now = time();
        $mh = curl_multi_init();
        $handles = array();
        foreach ($this->urls as $hash => $url) {
            if (is_file($url->file) && $now < filemtime($url->file) + MultigetConfig::$cacheSeconds) {
                $this->urls[$hash]->cached = true;
                continue;
            }
            $ch = curl_init($url->url);
            $fp = fopen($url->file, 'w');
            $options = MultigetConfig::$curlOptions;
            $options[CURLOPT_FILE] = $fp;
            curl_setopt_array($ch, $options);
            curl_multi_add_handle($mh, $ch);
            $handles[$hash] = array($fp, $ch);
        }

        if (!empty($handles)) {
            $running = null;
            do {
                curl_multi_exec($mh, $running);
            } while($running > 0);

            foreach($handles as $hash => $arr) {
                fclose($arr[0]);
                $url = $this->urls[$hash];
                $info = curl_getinfo($arr[1]);
                curl_multi_remove_handle($mh, $arr[1]);
                $this->urls[$hash]->info = $info;

                if (!preg_match('/\A[23]/', $info['http_code'])) {
                    $this->urls[$hash]->errored = true;
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
            if ($url->errored) {
                if (!empty($url->error)) {
                    $callback = $url->error;
                }
            } elseif(!empty($url->success)) {
                $callback = $url->success;
            }

            if (!empty($callback)) {
                $url->info += array(
                    'error' => $url->errored,
                    'cached' => $url->cached,
                    'url' => $url->url,
                    'raw_url' => $url->raw,
                    'file' => $url->file,
                    'length' => filesize($url->file),
                );
                $array = new \ArrayObject($url->info, \ArrayObject::ARRAY_AS_PROPS);
                if (!$this->isBinded($callback)) {
                    $callback = $callback->bindTo($array);
                }
                $result = $callback($url->file, $array);
            }

            yield $result;
        }
    }

    public function clean()
    {
        if (!empty(MultigetConfig::$storagePath)) {
            $reg = preg_quote(MultigetConfig::$filePrefix).'[a-zA-Z0-9]{32}'.preg_quote(MultigetConfig::$fileExt);
            foreach(scandir(MultigetConfig::$storagePath) as $file) {
                if (preg_match('/'.$reg.'/', $file)) {
                    unlink(MultigetConfig::$storagePath.$file);
                }
            }
        }
    }

    private function isBinded($closure)
    {
        $reflection = new \ReflectionFunction($closure);
        $bind = $reflection->getClosureThis();
        if (is_object($bind)) {
            return $bind;
        }
        return false;
    }
}
