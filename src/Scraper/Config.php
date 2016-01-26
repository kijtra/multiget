<?php
namespace Kijtra\Scraper;

class Config
{
    private $config = array(
        'user_agent' => 'Mozilla',
    );

    public function __construct(array $config)
    {
        $this->config = array_replace($this->config, $config);

        if (empty($config['temp_dir']) || !($dir = realpath($config['temp_dir']))) {
            $config['temp_dir'] = rtrim(sys_get_temp_dir(), '/\\').DIRECTORY_SEPARATOR;
        } else {
            $config['temp_dir'] = rtrim($config['temp_dir'], '/\\').DIRECTORY_SEPARATOR;
        }
    }

    public function get($key)
    {
        if (array_key_exists($key, $this->config)) {
            return $this->config[$key];
        }
    }
}
