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
                'name' => [
                    'required',
                    ['lengthMin', 5],
                    ['lengthMax', 250],
                    ['notIn', ['admin']],
                ],
                'username' => [
                    'required',
                    ['lengthMin', 3],
                    ['lengthMax', 250],
                    ['notIn', ['root', 'me']],
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
                'name' => [
                    ['optional'],
                    ['lengthMin', 5],
                    ['lengthMax', 250],
                    ['notIn', ['admin']],
                ],
                'username' => [
                    ['optional'],
                    ['lengthMin', 3],
                    ['lengthMax', 250],
                    ['notIn', ['root', 'me']],
                    'slug',
                    'checkUnique',
                ],
                'email' => [
                    ['optional'],
                    'email',
                    ['lengthMax', 250],
                    'checkUnique',
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
            'forgot' => [
                'email' => [
                    'required',
                    'email',
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
            if ($user = UserModel::where([$field => $value])->getResult()) {
                return !empty($this->params) && in_array($user->id, (array) (array_get($this->params, 'id') ?: []));
            }
        }
        return true;
    }
}
