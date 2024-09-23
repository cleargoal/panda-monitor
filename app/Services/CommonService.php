<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CommonService
{

    /**
     * Read resource by URL
     * @param string $sourceUrl
     * @return string
     */
    public function readSource(string $sourceUrl): string
    {
        $srcFile = '';
        try {
            $content = file_get_contents($sourceUrl);
            $srcFile = Str::random(8);
            Storage::put('sources/' . $srcFile . '.txt', $content);
        } catch (\Exception $exception) {
            Log::error($exception->getMessage(), ['method' => 'getSource', 'url' => $sourceUrl]);
        }
        return $srcFile;
    }

    public function getDataFromFile($srcFile): array
    {
        $contentArray = explode("\n", Storage::get('sources/' . $srcFile . '.txt'));
        $jsonString = '';
        $advertData = [];
        foreach ($contentArray as $item) {
            if (str_contains($item, '@context')) {
                $scriptStart = '@context';
                $dataStartPosition = stripos($item, $scriptStart) - 2;
                $scriptEnd = '</script><script defer="defer"';
                $dataEndPosition = stripos($item, $scriptEnd);
                $jsonString = substr($item, $dataStartPosition, $dataEndPosition - $dataStartPosition);
                $advertData = json_decode($jsonString, true);
                break;
            }
        }
        return ['active' => $jsonString !== '', 'advertData' => $advertData];
    }

    public function removeTempFile($srcFile): void
    {
        $delPath = 'sources/' . $srcFile . '.txt';
        Storage::delete($delPath);
    }

}
