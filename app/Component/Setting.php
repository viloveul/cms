<?php

namespace App\Component;

use Viloveul\Mutator\Payload;
use Viloveul\Cache\Contracts\Cache;
use App\Entity\Setting as SettingModel;
use Viloveul\Mutator\Contracts\Manager as Mutator;

class Setting
{
    /**
     * @var mixed
     */
    protected $cache;

    /**
     * @var mixed
     */
    protected $mutator;

    /**
     * @var array
     */
    protected $options = [];

    /**
     * @param Cache $cache
     */
    public function __construct(Cache $cache, Mutator $mutator)
    {
        $this->cache = $cache;
        $this->mutator = $mutator;
        if (!$this->cache->has('setting.options')) {
            $this->load();
        } else {
            $this->options = $this->cache->get('setting.options') ?: [];
        }
    }

    /**
     * @return mixed
     */
    public function all(): array
    {
        $options = [];
        foreach ($this->options as $key => $value) {
            $options[$key] = $this->get($key);
        }
        return $options;
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
        $value = array_get($this->options, $name, $default);
        $map = $this->mutator->apply("setting.get", new Payload(compact('name', 'value')));
        return $map->value;
    }

    public function load(): void
    {
        $settings = SettingModel::findAll();
        foreach ($settings as $setting) {
            $values = json_decode($setting->option, true);
            $this->options[$setting->name] = json_last_error() === JSON_ERROR_NONE ? $values : $setting->option;
        }
        $this->cache->set('setting.options', $this->options);
    }
}
