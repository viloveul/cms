<?php

namespace App\Validation;

use App\Entity\Tag as TagModel;
use Viloveul\Validation\Validator;

class Tag extends Validator
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
        if (!empty($value)) {
            if ($tag = TagModel::where([$field => $value])->getResult()) {
                return !empty($this->params) && in_array($tag->id, (array) (array_get($this->params, 'id') ?: []));
            }
        }
        return true;
    }
}
