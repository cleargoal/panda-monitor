<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\SubscribeJob;
use App\Models\Advert;
use App\Models\User;
use App\Notifications\SubscribeNotification;
use Illuminate\Foundation\Bus\PendingDispatch;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SubscribeService
{

    private string $srcFile;
    private string $jsonString;
    private array $jsonObj;
    private int $createdAdvertId;


    /**
     * Dispatch job
     * @param User $user
     * @param array $data
     * @return PendingDispatch
     */
    public function subscribe(User $user, array $data): PendingDispatch
    {
        return SubscribeJob::dispatch($user, $data);
    }

    public function getAdvertProcess(User $user, string $sourceUrl, string $targetEmail): void
    {
        $this->readSource($sourceUrl);
        $this->getJsonFromFile();
        $this->saveToDb($user->id, $sourceUrl, $targetEmail);
        $this->notifyUser($user, $sourceUrl, $targetEmail);
        $this->removeTempFile();
    }

    protected function readSource(string $sourceUrl): void
    {
        try {
            $content = file_get_contents($sourceUrl);
            $this->srcFile = Str::random(8);
            file_put_contents(storage_path('sources/' . $this->srcFile . '.txt'), $content);
        } catch (\Exception $exception) {
            // retry Job sequentially or later
        }
    }


    protected function getJsonFromFile(): void
    {
        $contentArray = file(storage_path('sources/' . $this->srcFile . '.txt'));
        $this->jsonString = '';
        foreach ($contentArray as $index => $item) {
            if (str_contains($item, '@context')) {
                $scriptStart = '@context';
                $dataStartPosition = stripos($item, $scriptStart) - 2;

                $scriptEnd = '</script><script defer="defer"';
                $dataEndPosition = stripos($item, $scriptEnd);
                $this->jsonString = substr($item, $dataStartPosition, $dataEndPosition - $dataStartPosition);
                break;
            }
        }
    }

    protected function saveToDb(int $userId, string $sourceUrl, string $targetEmail): void
    {
        $this->jsonObj = json_decode($this->jsonString, true);
        // object gets data: $jsonObj['offers']['priceCurrency'], $jsonObj['offers']['price'], $jsonObj['name']

        $newAdvert = new Advert();
        $newAdvert->url = $sourceUrl;
        $newAdvert->email = $targetEmail;
        $newAdvert->name = $this->jsonObj['name'];
        $newAdvert->price = $this->jsonObj['offers']['price'];
        $newAdvert->save();
        $this->createdAdvertId = $newAdvert->id;

        $user = User::find($userId);
        $user->adverts()->attach($newAdvert->id);
    }

    protected function notifyUser(User $user, string $sourceUrl, string $targetEmail): void
    {
        $mailData = [
            'userId' => $user,
            'advertId' => $this->createdAdvertId,
            'sourceUrl' => $sourceUrl,
            'targetEmail' => $targetEmail,
            'currency' => $this->jsonObj['offers']['priceCurrency'],
            'name' => $this->jsonObj['name'],
            'price' => $this->jsonObj['offers']['price'],
        ];
        $user->notify(new SubscribeNotification($mailData));
    }

    protected function removeTempFile(): void
    {
        Log::info('removeTempFile', [storage_path('sources/' . $this->srcFile . '.txt')]);
        Storage::delete(storage_path('sources/' . $this->srcFile . '.txt'));
    }

    public function removeSubscription($user, $advert)
    {
        $user->adverts()->detach($advert->id);
        return $advert->delete();
    }
}
