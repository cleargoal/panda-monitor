<?php

declare(strict_types=1);

namespace App\Dto;

use InvalidArgumentException;

readonly class StoreAdvertDto
{

    public function __construct(
        public string $url,
        public ?string $name = null,
        public ?float $price = null
    )
    {
        $this->validate();
    }

    private function validate(): void
    {
        if (is_null($this->url) || empty($this->url)) {
            throw new InvalidArgumentException('The URL field cannot be null or empty.');
        }
    }
}
