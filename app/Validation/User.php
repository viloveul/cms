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
                'username' => [
                    'required',
                    ['lengthMin', 5],
                    ['lengthMax', 250],
                    ['notIn', ['admin']],
                    'slug',
                    'checkUnique',
                ],
                'email' => [
                    'required',
                    'email',
                    'checkUnique',
                    ['lengthMax', 250],
                ],
                'password' => [
                    'required',
                    ['lengthMin', 6],
                    ['equals', 'passconf'],
                ],
                'passconf' => [
                    'required',
                ],
            ],
            'update' => [
                'username' => [
                    ['optional'],
                    ['lengthMin', 5],
                    ['lengthMax', 250],
                    ['notIn', ['admin']],
                    'slug',
                    'checkUnique',
                ],
                'email' => [
                    ['optional'],
                    'email',
                    'checkUnique',
                    ['lengthMax', 250],
                ],
                'password' => [
                    ['optional'],
                    ['lengthMin', 6],
                    ['equals', 'passconf'],
                ],
                'passconf' => [
                    ['optional'],
                ],
            ],
            'login' => [
                'username' => [
                    'required',
                ],
                'password' => [
                    'required',
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
