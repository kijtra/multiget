<?php
namespace Kijtra\Scraper\Binder;

class Excel
{
    public function getBindInstance($content)
    {
        $tmp = tmpfile();
        $meta = stream_get_meta_data($tmp);
        $file = $meta['uri'];
        file_put_contents($file, $content);
        $instance = \PHPExcel_IOFactory::load($file);
        fclose($tmp);
        return $instance;
    }
}
