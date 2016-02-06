<?php
namespace Kijtra\Multiget;

use Kijtra\Multiget\Config;

class Url
{
    public $raw;
    public $url;
    public $hash;
    public $file;
    public $info = array();

    public $ceched = false;
    public $errored = false;

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
        $this->hash = md5($clean);

        if (Config::$storagePath) {
            $this->file = Config::$storagePath.Config::$filePrefix.$this->hash.Config::$fileExt;
        } else {
            $this->file = tempnam(sys_get_temp_dir(), Config::$filePrefix);
        }
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
