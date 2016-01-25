<?php
namespace Kijtra\Scraper\Binder;

class DOM
{
    public function getBindInstance($content)
    {
        $crawler = new \Symfony\Component\DomCrawler\Crawler();
        $crawler->addContent($content);
        return $crawler;
    }
}
