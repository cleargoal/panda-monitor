<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\SubscribeJob;
use App\Models\Advert;
use App\Models\User;
use App\Notifications\AdvertMissingNotification;
use App\Notifications\SubscribeNotification;
use Illuminate\Support\Facades\Notification;

class SubscribeService
{

    private array $advertData;
    private int $createdAdvertId;
    private ParserService $service;

    public function __construct(ParserService $service)
    {
        $this->service = $service;
    }

    /**
     * Dispatch job
     * @param User $user
     * @param array $data
     * @return string
     */
    public function subscribe(User $user, array $data): string
    {
        $data['email'] = $data['email'] ?? null;
        if ($user->adverts()->where('url', $data['url'])->get()->count() === 0) {
            $data['advertId'] = $this->createAdvert($data['url']);
            SubscribeJob::dispatch($user, $data);
            return 'Successfully subscribed';
        } else {
            return 'You are already subscribed to this advert.';
        }
    }

    /**
     * Create new advert record
     * @param string $url
     * @return int
     */
    protected function createAdvert(string $url): int
    {
        $newAdvert = new Advert();
        $newAdvert->url = $url;
        $newAdvert->save();
        return $newAdvert->id;
    }

    public function subscriptionProcess(User $user, array $data): void
    {
        $this->advertData = $this->service->getDataFromPage($data['url']);
        if ($this->advertData['price']) {
            $this->saveDataToDb($user, $data);
            $this->notifyOnSuccessful($user, $data);
        } else {
            $user->notify(new AdvertMissingNotification('Unsuccessful subscription', $data['url']));
        }
    }

    /**
     * Save to adverts table the name and price from advert
     * @param User $user
     * @param array $data
     */
    protected function saveDataToDb(User $user, array $data): void
    {
        $this->createdAdvertId = $data['advertId'];
        $getAdvert = Advert::find($this->createdAdvertId);
        $getAdvert->name = $this->advertData['name'];
        $getAdvert->price = $this->advertData['price'];
        $getAdvert->save();

        $user->adverts()->attach($this->createdAdvertId, ['email' => $data['email']]);
    }

    /**
     * Successful notification
     * @param User $user
     * @param array $data
     */
    protected function notifyOnSuccessful(User $user, array $data): void
    {
        $mailData = [
            'advertId' => $this->createdAdvertId,
            'sourceUrl' => $data['url'],
            'name' => $this->advertData['name'],
            'price' => $this->advertData['price'],
        ];

        if ($data['email'] !== null) {
            Notification::route('mail', $data['email'])->notify(new SubscribeNotification($mailData));
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
