<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Str;

class ParserService
{

    public function getDataFromPage(string $sourceUrl): array|null
    {
        $content = file_get_contents($sourceUrl);
        $jsonString = Str::between($content, '<script data-rh="true" type="application/ld+json">', '</script><script defer="defer"');
        $advertData = json_decode($jsonString, true);
        return $advertData ? ['price' => $advertData['offers']['price'], 'name' => $advertData['name']] : ['price' => null, 'name' => null];
    }

}
