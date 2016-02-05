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
    private $curl;

    public function __construct($storage = null)
    {
        Config::setStorage($storage);
    }

    public function url($url)
    {
        $url = new Url($url);
        $this->stacks[$url->hash()] = new Stack($url);
        return $url;
    }

    public function run($closure = null)
    {
        $curl = new Curl();
        $this->stacks = $curl->save($this->stacks);

        if ($closure instanceof \Closure) {
            $response = array();
            foreach($this->generator() as $url => $res) {
                $response[$url] = $res;
            }
            $closure($response);
        } else {
            foreach($this->generator() as $url => $res) {
                continue;
            }
        }
    }

    private function generator()
    {
        foreach($this->stacks as $stack) {
            if (!$callback = $stack->getCallback()) {
                continue;
            }
            yield $callback($stack->url('raw'));
        }
    }
}
