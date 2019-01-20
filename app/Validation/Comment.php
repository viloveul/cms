<?php

namespace App\Validation;

use Viloveul\Validation\Validator;

class Comment extends Validator
{
    public function rules(): array
    {
        return [
            'insert' => [
                'post_id' => [
                    'required',
                ],
                'parent_id' => [
                    'required',
                ],
                'author_id' => [
                    'required',
                ],
                'fullname' => [
                    'required',
                    ['lengthMin', 5],
                    ['lengthMax', 250],
                ],
                'email' => [
                    'required',
                    'email',
                    ['lengthMax', 250],
                ],
                'content' => [
                    'required',
                ],
            ],
            'update' => [
                'post_id' => [
                    'required',
                ],
                'parent_id' => [
                    ['optional'],
                ],
                'author_id' => [
                    ['optional'],
                ],
                'fullname' => [
                    ['optional'],
                    ['lengthMin', 5],
                    ['lengthMax', 250],
                ],
                'email' => [
                    ['optional'],
                    'email',
                    ['lengthMax', 250],
                ],
                'content' => [
                    ['optional'],
                ],
            ],
        ];
    }
}
