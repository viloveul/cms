<?php

namespace App\Validation;

use App\Entity\Post as PostModel;
use Viloveul\Validation\Validator;

class Post extends Validator
{
    public function boot()
    {
        $this->validator->addInstanceRule('checkUnique', [$this, 'unique'], '{field} already registered.');
    }

    public function rules(): array
    {
        return [
            'insert' => [
                'slug' => [
                    'required',
                    ['lengthMin', 5],
                    ['lengthMax', 250],
                    'slug',
                    'checkUnique',
                ],
                'title' => [
                    'required',
                    ['lengthMin', 5],
                    ['lengthMax', 250],
                ],
                'type' => [
                    'required',
                ],
                'content' => [
                    'required',
                ],
            ],
            'update' => [
                'slug' => [
                    ['optional'],
                    ['lengthMin', 5],
                    ['lengthMax', 250],
                    'slug',
                    'checkUnique',
                ],
                'title' => [
                    ['optional'],
                    ['lengthMin', 5],
                    ['lengthMax', 250],
                ],
                'type' => [
                    ['optional'],
                ],
                'content' => [
                    ['optional'],
                ],
            ],
        ];
    }

    /**
     * @param $field
     * @param $value
     * @param array    $params
     * @param array    $fields
     */
    public function unique($field, $value, array $params, array $fields)
    {
        if ($post = PostModel::where([$field => $value])->getResult()) {
            return !empty($this->params) && in_array($post->id, (array) (array_get($this->params, 'id') ?: []));
        }
        return true;
    }
}
