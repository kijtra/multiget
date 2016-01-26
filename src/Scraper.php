<?php
namespace Kijtra;

use Kijtra\Scraper\Logger;
use Kijtra\Scraper\Errors;
use Kijtra\Scraper\Urls;
use Kijtra\Scraper\MultiRequest;

class Scraper
{
    private $config = array(
        'user_agent' => 'Mozilla',
    );
    private $logger;
    private $errors;
    private $container = array();

    public function __construct()
    {
        foreach(func_get_args() as $arg) {
            if ($arg instanceof Logger && null === $this->logger) {
                $this->logger = $arg;
            } elseif ($arg instanceof Errors && null === $this->errors) {
                $this->errors = $arg;
            }
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
            $this->logger,
            $this->errors
        );
        $request->setUserAgent($this->config['user_agent']);

        // TODO: Loggerが有効でも強制的に読み込みたい場合に備えてcontainerを渡すべき？
        $contents = $request->getContents($urls);

        foreach($this->containers as $container) {
            $url = $container->getUrl();
            if (!empty($contents[$url])) {
                $callback = $container->getCallback();
                if (is_callable($callback)) {
                    $content = file_get_contents($contents[$url]);
                    // unlink($contents[$url]);
                    try {
                        if ($binder = $container->getBinder($content)) {
                            $callback = $callback->bindTo($binder);
                        }
                    } catch(\Exception $e) {
                        throw $e;
                    }

                    $callback($content);
                }
            }
        }
    }
}
