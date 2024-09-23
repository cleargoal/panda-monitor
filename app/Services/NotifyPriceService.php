<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Advert;
use App\Models\User;
use App\Notifications\AdvertMissingNotification;
use App\Notifications\PriceChangedNotification;
use App\Notifications\SubscribeNotification;
use Illuminate\Support\Facades\Notification;

class NotifyPriceService
{

    public function process(): void
    {
        $changedPrices = Advert::whereDate('updated_at', today())
            ->whereDate('created_at', '!=', today())
            ->with('users')
            ->get();

        if ($changedPrices->isNotEmpty()) {
            $userAdverts = [];
            foreach ($changedPrices as $advert) {
                foreach ($advert->users as $user) {
                    $userAdverts[$user->id]['user'] = $user;
                    $userAdverts[$user->id]['adverts'][] = $advert;
                }
            }

            foreach ($userAdverts as $userData) {
                $this->notifyUserOfChanges($userData['user'], $userData['adverts']);
            }
        }
    }

    /**
     * Notify user with all changed adverts in one email
     *
     * @param User $user
     * @param array $adverts
     */
    public function notifyUserOfChanges(User $user, array $adverts): void
    {
        $mailData = [];

        foreach ($adverts as $advert) {
            $mailData[] = [
                'sourceUrl' => $advert->url,
                'price' => $advert->price,
            ];
        }

        $advertUserPivot = $user->adverts()->whereIn('adverts.id', collect($adverts)->pluck('id'))->first();

        if ($advertUserPivot && $advertUserPivot->pivot && $advertUserPivot->pivot->email) {
            Notification::route('mail', $advertUserPivot->pivot->email)->notify(new PriceChangedNotification($mailData));
        } else {
            $user->notify(new PriceChangedNotification($mailData));
        }
    }

    public function notifyMissingAdvert(int $advertId, string $sourceUrl): void
    {
        $advert = Advert::with('users')->find($advertId);
        $users = $advert->users;
        $subject = 'Advert does not exist';

        foreach ($users as $user) {
            $pivot = $user->pivot;

            if ($pivot && $pivot->email) {
                Notification::route('mail', $pivot->email)
                    ->notify(new AdvertMissingNotification($subject, $sourceUrl));
            } else {
                $user->notify(new AdvertMissingNotification($subject, $sourceUrl));
            }
        }
    }

}
