<?php

namespace App\Validation;

use App\Entity\User as UserModel;
use Valitron\Validator;

class User
{
    /**
     * @var mixed
     */
    protected $params = [];

    /**
     * @var array
     */
    protected $rules = [
        'store' => [
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
        'edit' => [
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

    /**
     * @var mixed
     */
    protected $validator;

    /**
     * @param array $data
     */
    public function __construct(array $data, array $params = [])
    {
        $this->params = $params;
        $this->validator = new Validator($data);
        $this->validator->addInstanceRule('checkUnique', [$this, 'unique'], '{field} already registered.');
    }

    /**
     * @return mixed
     */
    public function errors()
    {
        return $this->validator->errors();
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

    /**
     * @return mixed
     */
    public function validate($rule)
    {
        $this->validator->mapFieldsRules($this->rules[$rule]);
        return $this->validator->validate();
    }
}
