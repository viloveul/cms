<?php

namespace App\Validation;

use Viloveul\Validation\Validator;

class MenuItem extends Validator
{
    public function rules(): array
    {
        return [
            'insert' => [
                'menu_id' => [
                    'required',
                ],
                'parent_id' => [
                    ['optional'],
                ],
                'author_id' => [
                    ['optional'],
                ],
                'label' => [
                    'required',
                ],
            ],
            'update' => [
                'menu_id' => [
                    'required',
                ],
                'parent_id' => [
                    ['optional'],
                ],
                'author_id' => [
                    ['optional'],
                ],
                'label' => [
                    'required',
                ],
            ],
        ];
    }
}
