<?php
namespace Kijtra\Scraper;

use Kijtra\Scraper\Errors;
use Kijtra\Scraper\Binder;

// TODO: パッケージ内に取り込みOK？
use Symfony\Component\DomCrawler\Crawler;

class Urls
{
    private $url;
    // private $isBind;
    private $errors;
    private $callback;
    private $binder;

    private $binders = array(
        'dom' => 'DOM',
        'excel' => 'Excel',
        'xls' => 'Excel',
        'pdf' => 'PDF',
    );

    public function __construct($url, Errors $errors = null)
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \Exception('Invalid URL "'.$url.'".');
        }

        $this->url = $url;
        $this->key = $this->getNormalizedUrl($url);
        $this->errors = $errors;
    }

    private function getNormalizedUrl($url)
    {
        $url - preg_replace('/index\.[a-zA-Z0-9]{2,5}\z/', '', $url);
        $url = rtrim($url, '/._');
        return $url;
    }

    public function getKey()
    {
        return $this->key;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function getCallback()
    {
        return $this->callback;
    }

    public function getBinder($content)
    {
        if (method_exists($this->binder, 'getBindInstance')) {
            return $this->binder->getBindInstance($content);
        } elseif(is_string($this->binder)) {
            $name = '\\'.$this->binder;
            return new $name($content);
        }
    }

    public function bind($key)
    {
        if (empty($key)) {
            $this->binder = null;
        } elseif(is_string($key)) {
            $key = strtolower($key);
            if (!empty($this->binders[$key])) {
                $class = __NAMESPACE__.'\\Binder\\'.$this->binders[$key];
                $this->binder = new $class();
            } else {
                $key = trim($key, '\\');
                if (class_exists('\\'.$key)) {
                    $this->binder = $key;
                }
            }
        } elseif(is_object($key)) {
            $this->binder = get_class($key);
        }

        return $this;
    }

    public function callback($callback)
    {
        if (!is_callable($callback)) {
            throw new \Exception('Callback object is not callable.');
        }

        $this->callback = $callback;
        return $this;
    }
}
