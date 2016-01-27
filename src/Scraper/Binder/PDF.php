<?php
namespace Kijtra\Scraper\Binder;

class PDF
{
    public function getBindInstance($tempfile)
    {
        if (!is_file($tempfile)) {
            return;
        }

        $parser = new \Smalot\PdfParser\Parser();
        $instance = $parser->parseFile($tempfile);
        return $instance;
    }
}
