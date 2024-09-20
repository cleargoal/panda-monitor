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
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class SubscribeService
{

    private string $srcFile;
    private string $jsonString;
    private array $jsonObj;
    private int $createdAdvertId;
    private Advert $newAdvert;

    /**
     * Dispatch job
     * @param User $user
     * @param array $data
     * @return PendingDispatch|string
     */
    public function subscribe(User $user, array $data): PendingDispatch | string
    {
        if($user->adverts()->where('url', $data['url'])->get()->count() === 0) {
            return SubscribeJob::dispatch($user, $data);
        }
        else {
            return 'You are already subscribed to this advert.';
        }
    }

    public function getAdvertProcess(User $user, string $sourceUrl, string $targetEmail): void
    {
        $this->readSource($sourceUrl);
        $this->getJsonFromFile();
        $this->saveToDb($user->id, $sourceUrl, $targetEmail);
        $this->notifyUser($user, $sourceUrl, $targetEmail);
        $this->removeTempFile();
    }

    /**
     * Read resource by URL
     * @param string $sourceUrl
     */
    protected function readSource(string $sourceUrl): void
    {
        try {
            $content = file_get_contents($sourceUrl);
            $this->srcFile = Str::random(8);
            Storage::put('sources/' . $this->srcFile . '.txt', $content);
        } catch (\Exception $exception) {
            // retry Job sequentially or later
        }
    }


    protected function getJsonFromFile(): void
    {
        $contentArray = explode("\n", Storage::get('sources/' . $this->srcFile . '.txt'));
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
        $validator = Validator::make(['email' => $targetEmail], [
            'email' => 'email',
        ]);

        $email = $validator->fails() ? null : $targetEmail;
        $this->jsonObj = json_decode($this->jsonString, true);
        // object gets data: $jsonObj['offers']['priceCurrency'], $jsonObj['offers']['price'], $jsonObj['name']

        $this->newAdvert = new Advert();
        $this->newAdvert->url = $sourceUrl;
        $this->newAdvert->email = $email;
        $this->newAdvert->name = $this->jsonObj['name'];
        $this->newAdvert->price = $this->jsonObj['offers']['price'];
        $this->newAdvert->save();
        $this->createdAdvertId = $this->newAdvert->id;

        $user = User::find($userId);
        $user->adverts()->attach($this->newAdvert->id);
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
        if ($this->newAdvert->email) {
            $this->newAdvert->notify(new SubscribeNotification($mailData));
        }
        else {
            $user->notify(new SubscribeNotification($mailData));
        }
    }

    protected function removeTempFile(): void
    {
        $delPath = 'sources/' . $this->srcFile . '.txt';
        Storage::delete($delPath);
    }

    public function removeSubscription(User $user, Advert $advert): ?bool
    {
        $user->adverts()->detach($advert->id);
        return $advert->delete();
    }
}
