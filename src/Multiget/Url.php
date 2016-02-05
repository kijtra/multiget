<?php
namespace Kijtra\Multiget;

use Kijtra\Multiget\Config;

class Url
{

    private $raw;
    private $url;
    private $hash;
    public $success;
    public $error;

    public function __construct($url)
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \Exception('Invalid URL "'.$url.'"');
        }

        $this->raw = $url;

        $clean = null;

        $url = parse_url($url);
        $clean .= $url['scheme']."://".$url['host'];

        if (empty($url['path'])) {
            $clean .= '/';
        } else {
            $clean .= $url['path'];
        }

        if (!empty($url['query'])) {
            $clean .= '?'.$url['query'];
        }

        $this->url = $clean;
        $this->hash = Config::$filePrefix.md5($clean);
    }

    public function raw()
    {
        return $this->raw;
    }

    public function url()
    {
        return $this->url;
    }

    public function hash()
    {
        return $this->hash;
    }

    public function success($closure = null)
    {
        if ($closure instanceof \Closure) {
            $this->success = $closure;
        }
        return $this;
    }

    public function error($closure = null)
    {
        if ($closure instanceof \Closure) {
            $this->error = $closure;
        }
        return $this;
    }
}
