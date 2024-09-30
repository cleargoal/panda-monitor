<?php

declare(strict_types=1);

namespace App\Services;

use App\Dto\StoreAdvertDto;
use App\Jobs\SubscribeJob;
use App\Models\Advert;
use App\Models\User;
use App\Notifications\AdvertMissingNotification;

class SubscribeService
{
    private ParserService $parser;
    private NotifyService $notifier;

    public function __construct(ParserService $parser, NotifyService $notifier)
    {
        $this->parser = $parser;
        $this->notifier  = $notifier;
    }

    /**
     * Dispatch job
     * @param User $user
     * @param StoreAdvertDto $dto
     * @return string
     */
    public function subscribe(User $user, StoreAdvertDto $dto): string
    {
        $email = $dto->email ?? null;

        if ($user->adverts()->where('url', $dto->url)->count() === 0) {
            $advertId = $this->createAdvert($dto->url);

            $jobData = [
                'url' => $dto->url,
                'email' => $email,
                'advertId' => $advertId,
            ];

            SubscribeJob::dispatch($user, $jobData);
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

    public function subscriptionProcess(User $user, array $jobData): void
    {
        $advertData = $this->parser->getDataFromPage($jobData['url']);
        if ($advertData['price']) {
            $this->saveDataToDb($user, $jobData, $advertData);
            $this->notifier->notifyOnSuccessful($user, $jobData, $advertData);
        } else {
            $user->notify(new AdvertMissingNotification('Unsuccessful subscription', $jobData['url']));
        }
    }

    /**
     * Save to adverts table the name and price from advert
     * @param User $user
     * @param array $jobData
     * @param array $advertData
     */
    protected function saveDataToDb(User $user, array $jobData, array $advertData): void
    {
        $getAdvert = Advert::find($jobData['advertId']);
        $getAdvert->name = $advertData['name'];
        $getAdvert->price = $advertData['price'];
        $getAdvert->save();

        $user->adverts()->attach($jobData['advertId'], ['email' => $jobData['email']]);
    }

    public function removeSubscription(User $user, Advert $advert): ?bool
    {
        $user->adverts()->detach($advert->id);
        return $advert->delete();
    }
}
