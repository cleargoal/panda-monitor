<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Advert;
use App\Models\User;
use App\Notifications\AdvertMissingNotification;
use App\Notifications\PriceChangedNotification;
use Illuminate\Support\Facades\Notification;

class NotifyService
{

    /**
     * Notify user with all changed adverts in one email
     *
     * @param User $user
     * @param array $adverts
     */
    public function notifyUserOfChanges(User $user, array $adverts): void
    {
        $mailData = collect($adverts)->map(function ($advert) {
            return [
                'sourceUrl' => $advert->url,
                'price' => $advert->price,
            ];
        })->toArray();

        $this->notifyUserOrPivot($user, $adverts, new PriceChangedNotification($mailData));
    }

    public function notifyMissingAdvert(int $advertId, string $sourceUrl): void
    {
        $advert = Advert::with('users')->find($advertId);
        $users = $advert->users;
        $subject = 'Advert does not exist';

        foreach ($users as $user) {
            $this->notifyUserOrPivot($user, [$advert], new AdvertMissingNotification($subject, $sourceUrl));
        }
    }

    private function notifyUserOrPivot(User $user, array $adverts, $notification): void
    {
        $advertUserPivot = $user->adverts()->whereIn('adverts.id', collect($adverts)->pluck('id'))->first();

        if ($advertUserPivot && $advertUserPivot->pivot && $advertUserPivot->pivot->email) {
            Notification::route('mail', $advertUserPivot->pivot->email)->notify($notification);
        } else {
            $user->notify($notification);
        }
    }

}
