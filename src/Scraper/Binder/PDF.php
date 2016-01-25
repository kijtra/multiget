<?php
namespace Kijtra\Scraper\Binder;

class PDF
{
    public function getBindInstance($content)
    {
        $parser = new \Smalot\PdfParser\Parser();
        $tmp = tmpfile();
        $meta = stream_get_meta_data($tmp);
        $file = $meta['uri'];
        file_put_contents($file, $content);
        $instance = $parser->parseFile($file);
        fclose($tmp);
        return $instance;
    }
}
