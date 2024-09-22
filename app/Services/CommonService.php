<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Notifications\AdvertMissingNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CommonService
{

    private array $jsonObj;
    private int $createdAdvertId;

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

    public function getJsonFromFile($srcFile): array
    {
        $contentArray = explode("\n", Storage::get('sources/' . $srcFile . '.txt'));
        $jsonString = '';
        foreach ($contentArray as $index => $item) {
            if (str_contains($item, '@context')) {
                $scriptStart = '@context';
                $dataStartPosition = stripos($item, $scriptStart) - 2;

                $scriptEnd = '</script><script defer="defer"';
                $dataEndPosition = stripos($item, $scriptEnd);
                $jsonString = substr($item, $dataStartPosition, $dataEndPosition - $dataStartPosition);
                $this->jsonObj = json_decode($jsonString, true);
                break;
            }
        }
        return ['active' => $jsonString !== '', 'jsonObj' => $this->jsonObj];
    }

    /**
     * Unsuccessful notification
     * @param User $user
     * @param string $sourceUrl
     */
    public function notifyUnsuccessful(User $user, string $sourceUrl): void
    {
        $mailData = [
            'sourceUrl' => $sourceUrl,
        ];

        $user->notify(new AdvertMissingNotification($mailData));
    }

    public function removeTempFile($srcFile): void
    {
        $delPath = 'sources/' . $srcFile . '.txt';
        Storage::delete($delPath);
    }

}
