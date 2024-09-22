<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\SubscribeJob;
use App\Models\Advert;
use App\Models\User;
use App\Notifications\AdvertMissingNotification;
use App\Notifications\SubscribeNotification;
use Illuminate\Foundation\Bus\PendingDispatch;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class SubscribeService
{

    private string $srcFile;
    private string $jsonString;
    private array $jsonObj;
    private int $createdAdvertId;
    private bool $activeAdvert = true;

    /**
     * Dispatch job
     * @param User $user
     * @param array $data
     * @return PendingDispatch|string
     */
    public function subscribe(User $user, array $data): PendingDispatch|string
    {
        if ($user->adverts()->where('url', $data['url'])->get()->count() === 0) {
            return SubscribeJob::dispatch($user, $data);
        } else {
            return 'You are already subscribed to this advert.';
        }
    }

    public function getAdvertProcess(User $user, string $sourceUrl, string $targetEmail): void
    {
        $this->readSource($sourceUrl);
        $this->getJsonFromFile();
        if ($this->activeAdvert) {
            $this->saveToDb($user, $sourceUrl, $targetEmail);
            $this->notifyOnSuccessful($user, $sourceUrl, $targetEmail);
            $this->removeTempFile();
        } else {
            $this->notifyUnsuccessful($user, $sourceUrl, $targetEmail);
        }
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
            Log::error($exception->getMessage(), ['method' => 'getSource', 'url' => $sourceUrl]);
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
                $this->jsonObj = json_decode($this->jsonString, true);
                break;
            }
        }
        if ($this->jsonString === '') {
            $this->activeAdvert = false;
        }
    }

    protected function saveToDb(User $user, string $sourceUrl, string $targetEmail): void
    {
        $validator = Validator::make(['email' => $targetEmail], [
            'email' => 'email',
        ]);
        $email = $validator->fails() ? null : $targetEmail;

        $advert = Advert::where('url', $sourceUrl)->first();

        if ($advert) {
            $this->createdAdvertId = $advert->id;
        } else {
            $newAdvert = new Advert();
            $newAdvert->url = $sourceUrl;
            $newAdvert->name = $this->jsonObj['name'];
            $newAdvert->price = $this->jsonObj['offers']['price'];
            $newAdvert->save();
            $this->createdAdvertId = $newAdvert->id;
        }

        $user->adverts()->attach($this->createdAdvertId, ['email' => $email]);
    }

    /**
     * Successful notification
     * @param User $user
     * @param string $sourceUrl
     * @param string $targetEmail
     */
    protected function notifyOnSuccessful(User $user, string $sourceUrl, string $targetEmail): void
    {
        $mailData = [
            'advertId' => $this->createdAdvertId,
            'sourceUrl' => $sourceUrl,
            'targetEmail' => $targetEmail,
            'currency' => $this->jsonObj['offers']['priceCurrency'],
            'name' => $this->jsonObj['name'],
            'price' => $this->jsonObj['offers']['price'],
        ];

        // Get the email from the pivot table (advert_user)
        $advertUserPivot = $user->adverts()->where('advert_id', $this->createdAdvertId)->first();

        if ($advertUserPivot && $advertUserPivot->pivot && $advertUserPivot->pivot->email) {
            Notification::route('mail', $advertUserPivot->pivot->email)->notify(new SubscribeNotification($mailData));
        } else {
            $user->notify(new SubscribeNotification($mailData));
        }
    }

    /**
     * Unsuccessful notification
     * @param User $user
     * @param string $sourceUrl
     * @param string $targetEmail
     */
    protected function notifyUnsuccessful(User $user, string $sourceUrl, string $targetEmail): void
    {
        $mailData = [
            'sourceUrl' => $sourceUrl,
            'targetEmail' => $targetEmail,
        ];

        $user->notify(new AdvertMissingNotification($mailData));
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
