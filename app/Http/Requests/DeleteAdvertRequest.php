<?php

declare(strict_types = 1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeleteAdvertRequest extends FormRequest
{
    public function authorize(): true
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'advert' => 'required|integer|exists:adverts,id',
        ];
    }

    public function attributes(): array
    {
        return [
            'advert' => 'advert ID',
        ];
    }
}
