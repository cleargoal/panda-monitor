<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Dto\StoreAdvertDto;
use Illuminate\Foundation\Http\FormRequest;

class CreateSubscriptionRequest extends FormRequest
{

    public function rules(): array
    {
        return [
            'url' => ['required', 'string', 'url'],
            'email' => ['nullable', 'string', 'email'],
        ];
    }

    public function messages(): array
    {
        return [
            'url.required' => 'The URL is required.',
            'url.url' => 'The URL must be a valid format.',
            'email.email' => 'The email must be a valid email address or empty field.',
        ];
    }

    public function toDto(): StoreAdvertDto
    {
        return new StoreAdvertDto(
            $this->validated()['url'],
            $this->validated()['name'] ?? null,
            $this->validated()['price'] ?? null
        );
    }
}
