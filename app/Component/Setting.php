<?php

namespace App\Component;

use App\Entity\Setting as SettingModel;
use Viloveul\Cache\Contracts\Cache;

class Setting
{
    /**
     * @var mixed
     */
    protected $cache;

    /**
     * @var array
     */
    protected $options = [];

    /**
     * @param Cache $cache
     */
    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
        if (!$this->cache->has('setting.options')) {
            $this->load();
        } else {
            $this->options = $this->cache->get('setting.options') ?: [];
        }
    }

    public function clear()
    {
        if ($this->cache->has('setting.options')) {
            $this->cache->delete('setting.options');
            $this->options = [];
        }
    }

    /**
     * @param $name
     * @param $default
     */
    public function get($name, $default = null)
    {
        return array_key_exists($name, $this->options) ? $this->options[$name] : $default;
    }

    public function load()
    {
        $settings = SettingModel::all();
        foreach ($settings as $setting) {
            $values = json_decode($setting->option);
            $this->options[$setting->name] = json_last_error() === JSON_ERROR_NONE ? $values : $setting->option;
        }
        $this->cache->set('setting.options', $this->options);
    }
}
