Simple Scraping library

```php
$process = new Kijtra\Scraper();

$process->url('http://google.com/')
->callback(function($content) {
    // ...something...
});

$process->url('http://google.com/')
->bind('dom')// bint to symfony/dom-crawler
->callback(function($content) {
    // binded symfony/dom-crawler
    $pageTitle = $this->filter('title')->text();
});

// exec (with curl multi request)
$process->exec();
```
