<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateSubscriptionRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'url' => ['required', 'string', 'url'],
            'email' => ['nullable', 'string', 'email'],
        ];
    }

    public function messages()
    {
        return [
            'url.required' => 'The URL is required.',
            'url.url' => 'The URL must be a valid format.',
            'email.email' => 'The email must be a valid email address or empty field.',
        ];
    }
}
