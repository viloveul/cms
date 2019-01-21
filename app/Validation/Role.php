<?php

namespace App\Validation;

use App\Entity\Role as RoleModel;
use Viloveul\Validation\Validator;

class Role extends Validator
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
                    'checkUnique',
                ],
                'type' => [
                    'required',
                    ['lengthMin', 5],
                    ['lengthMax', 250],
                ],
            ],
            'update' => [
                'name' => [
                    ['optional'],
                    ['lengthMin', 5],
                    ['lengthMax', 250],
                    'checkUnique',
                ],
                'type' => [
                    ['optional'],
                    ['lengthMin', 5],
                    ['lengthMax', 250],
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
        if ($role = RoleModel::where($field, $value)->first()) {
            return !empty($this->params) && in_array($role->id, (array) (array_get($this->params, 'id') ?: []));
        }
        return true;
    }
}
