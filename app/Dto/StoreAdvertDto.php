<?php

declare(strict_types=1);

namespace App\Dto;

use InvalidArgumentException;

class StoreAdvertDto
{
    public string $url;
    public ?string $name;
    public ?float $price;

    public function __construct(string $url, ?string $name = null, ?float $price = null)
    {
        $this->url = $url;
        $this->name = $name;
        $this->price = $price;

        $this->validate();
    }

    private function validate(): void
    {
        if (is_null($this->url) || empty($this->url)) {
            throw new InvalidArgumentException('The URL field cannot be null or empty.');
        }
    }
}
