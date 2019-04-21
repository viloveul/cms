<?php

namespace App\Component;

class Slug
{
    /**
     * @param  string  $model
     * @param  string  $field
     * @param  string  $slug
     * @return mixed
     */
    public function check(string $model, string $field, string $slug)
    {
        return $model::select('id')->where([$field => $slug])->getValue('id');
    }

    public static function create()
    {
        return new static();
    }

    /**
     * @param  string  $model
     * @param  string  $field
     * @param  string  $slug
     * @param  $id
     * @return mixed
     */
    public function generate(string $model, string $field, string $slug, $id = null): string
    {
        $slug = preg_replace('/[^a-z0-9\-\_]+/', '-', strtolower($slug));
        $suffix = '';
        $increase = 1;

        do {
            if ($res = $this->check($model, $field, $slug . $suffix)) {
                if (null !== $id && $res == $id) {
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
