<?php
namespace Kijtra\Scraper\Binder;

class DOM
{
    public function getBindInstance($tempfile)
    {
        if (!is_file($tempfile)) {
            return;
        }

        $content = file_get_contents($tempfile);
        $crawler = new \Symfony\Component\DomCrawler\Crawler();
        $crawler->addContent($content);
        unlink($tempfile);
        return $crawler;
    }
}
