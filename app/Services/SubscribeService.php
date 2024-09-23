<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\SubscribeJob;
use App\Models\Advert;
use App\Models\User;
use App\Notifications\SubscribeNotification;
use Illuminate\Foundation\Bus\PendingDispatch;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;

class SubscribeService
{

    private array $advertData;
    private int $createdAdvertId;
    private CommonService $service;

    public function __construct(CommonService $service)
    {
        $this->service = $service;
    }

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

    public function subscriptionProcess(User $user, string $sourceUrl, string $targetEmail): void
    {
        $srcFile = $this->service->readSource($sourceUrl);
        $activeAdvert = $this->service->getJsonFromFile($srcFile);
        if ($activeAdvert['active']) {
            $this->advertData = $activeAdvert['advertData'];
            $this->saveToDb($user, $sourceUrl, $targetEmail);
            $this->notifyOnSuccessful($user, $sourceUrl);
            $this->service->removeTempFile($srcFile);
        } else {
            $this->service->notifyUnsuccessful($user, $sourceUrl);
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
            $newAdvert->name = $this->advertData['name'];
            $newAdvert->price = $this->advertData['offers']['price'];
            $newAdvert->save();
            $this->createdAdvertId = $newAdvert->id;
        }

        $user->adverts()->attach($this->createdAdvertId, ['email' => $email]);
    }

    /**
     * Successful notification
     * @param User $user
     * @param string $sourceUrl
     */
    protected function notifyOnSuccessful(User $user, string $sourceUrl): void
    {
        $mailData = [
            'advertId' => $this->createdAdvertId,
            'sourceUrl' => $sourceUrl,
            'currency' => $this->advertData['offers']['priceCurrency'],
            'name' => $this->advertData['name'],
            'price' => $this->advertData['offers']['price'],
        ];

        // Get the email from the pivot table (advert_user)
        $advertUserPivot = $user->adverts()->where('advert_id', $this->createdAdvertId)->first();

        if ($advertUserPivot && $advertUserPivot->pivot && $advertUserPivot->pivot->email) {
            Notification::route('mail', $advertUserPivot->pivot->email)->notify(new SubscribeNotification($mailData));
        } else {
            $user->notify(new SubscribeNotification($mailData));
        }
    }

    public function removeSubscription(User $user, Advert $advert): ?bool
    {
        $user->adverts()->detach($advert->id);
        return $advert->delete();
    }
}
