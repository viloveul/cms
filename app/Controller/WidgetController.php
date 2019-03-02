<?php

namespace App\Controller;

use App\Component\Privilege;
use App\Component\Setting;
use Viloveul\Container\ContainerAwareTrait;
use Viloveul\Container\ContainerException;
use Viloveul\Container\Contracts\ContainerAware;
use Viloveul\Http\Contracts\Response;
use Viloveul\Http\Contracts\ServerRequest;
use Viloveul\Router\Contracts\Dispatcher;

class WidgetController implements ContainerAware
{
    use ContainerAwareTrait;

    /**
     * @var mixed
     */
    protected $privilege;

    /**
     * @var mixed
     */
    protected $request;

    /**
     * @var mixed
     */
    protected $response;

    /**
     * @var mixed
     */
    protected $route;

    /**
     * @param ServerRequest $request
     * @param Response      $response
     * @param Privilege     $privilege
     * @param Dispatcher    $router
     */
    public function __construct(ServerRequest $request, Response $response, Privilege $privilege, Dispatcher $router)
    {
        $this->request = $request;
        $this->response = $response;
        $this->privilege = $privilege;
        $this->route = $router->routed();
    }

    /**
     * @return mixed
     */
    public function availables()
    {
        $dir = realpath(__DIR__ . '/../Widget');
        $contains = scandir($dir);
        $items = array_filter($contains, function ($item) use ($dir) {
            return !in_array($item, ['.', '..']) && is_file($dir . '/' . $item);
        });
        $results = [];
        foreach ($items as $class) {
            $name = pathinfo($class, PATHINFO_FILENAME);
            if ($object = $this->make($name)) {
                $results[] = [
                    'name' => $name,
                    'options' => $object->getOptions(),
                ];
            }
        }
        return $results;
    }

    /**
     * @param string  $type
     * @param Setting $setting
     */
    public function load(string $type = 'sidebar', Setting $setting)
    {
        $results = [];
        $items = $setting->get('widget-' . $type) ?: [];
        foreach ($items as $item) {
            if (isset($item['name'])) {
                $options = array_key_exists('options', $item) ? $item['options'] : [];
                if ($object = $this->make($item['name'], $options)) {
                    $results[] = [
                        'name' => $item['name'],
                        'options' => $object->getOptions(),
                        'results' => $object->results(),
                    ];
                }
            }
        }
        return $results;
    }

    /**
     * @param  $class
     * @param  array    $options
     * @return mixed
     */
    protected function make($class, array $options = [])
    {
        try {
            $item = $this->getContainer()->make("\\App\\Widget\\{$class}");
            $item->setOptions($options);
            return $item;
        } catch (ContainerException $e) {
            return false;
        }
    }
}
