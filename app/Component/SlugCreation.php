<?php

namespace App\Component;

use Viloveul\Kernel\Contracts\Model;

class SlugCreation
{
    /**
     * @param  Model   $model
     * @param  string  $field
     * @param  string  $slug
     * @return mixed
     */
    public function check(Model $model, string $field, string $slug)
    {
        return $model::query()->where($field, $slug)->first();
    }

    /**
     * @param  Model   $model
     * @param  string  $field
     * @param  string  $slug
     * @param  int     $id
     * @return mixed
     */
    public function generate(Model $model, string $field, string $slug, int $id = null): string
    {
        $slug = preg_replace('/[^a-z0-9\-\:\.]+/', '-', strtolower($slug));
        $suffix = '';
        $increase = 1;

        do {
            if ($res = $this->check($model, $field, $slug . $suffix)) {
                if (!is_null($id) && $res->id == $id) {
                    return $slug . $suffix;
                } else {
                    $increase++;
                }
            } else {
                return $slug . $suffix;
            }

            $suffix = '-' . $increase;

        } while (true);

        return $slug;
    }
}
