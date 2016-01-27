<?php
namespace Kijtra\Scraper\Binder;

class Excel
{
    public function getBindInstance($tempfile)
    {
        if (!is_file($tempfile)) {
            return;
        }

        $instance = \PHPExcel_IOFactory::load($tempfile);
        unlink($tempfile);
        return $instance;
    }
}
