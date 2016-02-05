<?php
namespace Kijtra\Multiget;

use Kijtra\Multiget\Config;
use Kijtra\Multiget\Url;

class Stack
{
    private $url;
    private $filepath;
    private $cached = false;
    private $error = array();

    public function __construct($url)
    {
        if (!($url instanceof Url)) {
            throw new \Exception('Argument must be instance of Url.');
        }

        $this->url = $url;
        if (Config::$storagePath) {
            $this->filepath = Config::$storagePath.$url->hash().Config::$fileExt;
        } else {
            $this->filepath = tempnam(sys_get_temp_dir(), Config::$filePrefix);
        }
    }

    public function url($key = null)
    {
        if ('hash' === $key) {
            return $this->url->hash();
        } elseif('raw' === $key) {
            return $this->url->raw();
        } else {
            return $this->url->url();
        }
    }

    public function isFile()
    {
        return is_file($this->filepath);
    }

    public function setError($error)
    {
        $this->error = $error;
    }

    public function setCached($flag = false)
    {
        $this->cached = $flag;
    }

    public function getFile()
    {
        return $this->filepath;
    }

    public function getCallback()
    {
        if (!empty($this->error) && !empty($this->url->error)) {
            if (!empty($this->url->error)) {
                $closure = $this->url->error;
                $closure = $closure->bindTo(new \ArrayIterator($this->error));
                return $closure;
            }
        } elseif(!empty($this->url->success)) {
            $closure = $this->url->success;
            $closure = $closure->bindTo(new \ArrayIterator(array(
                'url' => $this->url('url'),
                'raw_url' => $this->url('raw'),
                'cached' => $this->cached,
                'file' => $this->filepath,
                'filetime' => date('c', filemtime($this->filepath)),
                'cachetime' => date('c', fileatime($this->filepath)),
                'length' => filesize($this->filepath),
            )));
            return $closure;
        }
    }
}
