<?php

namespace App\Component;

use App\Entity\Setting as SettingModel;
use Viloveul\Cache\Contracts\Cache;
use Viloveul\Event\Contracts\Dispatcher as Event;

class Setting
{
    /**
     * @var mixed
     */
    protected $cache;

    /**
     * @var mixed
     */
    protected $event;

    /**
     * @var array
     */
    protected $options = [];

    /**
     * @param Cache $cache
     */
    public function __construct(Cache $cache, Event $event)
    {
        $this->cache = $cache;
        $this->event = $event;
        if (!$this->cache->has('setting.options')) {
            $this->load();
        } else {
            $this->options = $this->cache->get('setting.options') ?: [];
        }
    }

    public function clear(): void
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
    public function get(string $name, $default = null)
    {
        $value = array_key_exists($name, $this->options) ? $this->options[$name] : $default;
        return $this->event->dispatch("setting.{$name}", $value);
    }

    public function load(): void
    {
        $settings = SettingModel::all();
        foreach ($settings as $setting) {
            $values = json_decode($setting->option);
            $this->options[$setting->name] = json_last_error() === JSON_ERROR_NONE ? $values : $setting->option;
        }
        $this->cache->set('setting.options', $this->options);
    }
}
