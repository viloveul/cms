<?php

namespace App\Validation;

use App\Entity\User as UserModel;
use Viloveul\Validation\Validator;

class User extends Validator
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
        if ($user = UserModel::where($field, $value)->first()) {
            return !empty($this->params) && in_array($user->id, $this->params);
        }
        return true;
    }
}
