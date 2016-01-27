<?php
namespace Kijtra;

use Kijtra\Scraper\Urls;
use Kijtra\Scraper\MultiRequest;

class Scraper
{
    // private $config = array(
    //     'user_agent' => 'Mozilla',
    // );

    private $config;
    private $logger;
    private $errors;
    private $container = array();

    public function __construct()
    {
        foreach(func_get_args() as $arg) {
            if ($arg instanceof Scraper\Config && null === $this->config) {
                $this->config = $arg;
            } elseif ($arg instanceof Scraper\Logger && null === $this->logger) {
                $this->logger = $arg;
            } elseif ($arg instanceof Scraper\Errors && null === $this->errors) {
                $this->errors = $arg;
            }
        }

        if (empty($this->config)) {
            $this->config = new Scraper\Config();
        }
    }

    public function url($url)
    {
        // TODO: Loggerが有効でも強制的に読み込みたい場合の対処
        $container = new Urls($url, $this->errors);
        $key = $container->getKey();
        if (empty($this->containers[$key])) {
            $this->containers[$key] = $container;
        }
        return $container;
    }

    public function exec()
    {
        if (empty($this->containers)) {
            return;
        }

        $urls = array();
        foreach($this->containers as $key => $container) {
            $urls[$key] = $container->getUrl();
        }

        $request = new MultiRequest(
            $this->config,
            $this->logger,
            $this->errors
        );
        $request->setUserAgent($this->config->get('user_agent'));

        // TODO: Loggerが有効でも強制的に読み込みたい場合に備えてcontainerを渡すべき？
        $tempfiles = $request->getTemps($urls);

        foreach($this->containers as $container) {
            $url = $container->getUrl();
            if (!empty($tempfiles[$url])) {
                $temp = $tempfiles[$url];
                $callback = $container->getCallback();
                if (is_callable($callback)) {
                    try {
                        if ($binder = $container->getBinder($temp)) {
                            $callback = $callback->bindTo($binder);
                        }
                    } catch(\Exception $e) {
                        throw $e;
                    }

                    $callback($temp);
                }
            }
        }
    }
}
