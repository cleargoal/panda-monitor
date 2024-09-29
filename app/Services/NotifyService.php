<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Advert;
use App\Models\User;
use App\Notifications\AdvertMissingNotification;
use App\Notifications\PriceChangedNotification;
use App\Notifications\SubscribeNotification;
use Illuminate\Support\Facades\Notification;

class NotifyService
{

    /**
     * Successful notification
     * @param User $user
     * @param array $jobData
     * @param array $advertData
     */
    public function notifyOnSuccessful(User $user, array $jobData, array $advertData): void
    {
        $mailData = [
            'advertId' => $jobData['advertId'],
            'sourceUrl' => $jobData['url'],
            'name' => $advertData['name'],
            'price' => $advertData['price'],
        ];

        if ($jobData['email'] !== null) {
            Notification::route('mail', $jobData['email'])->notify(new SubscribeNotification($mailData));
        } else {
            $user->notify(new SubscribeNotification($mailData));
        }
    }

    /**
     * Notify user with all changed adverts in one email
     *
     * @param User $user
     * @param array $adverts
     * @param string $email
     */
    public function notifyUserOfChanges(User $user, array $adverts, string $email): void
    {
        $mailData = [];
        foreach ($adverts as $advert) {
            $mailData[] = [
                'sourceUrl' => $advert->url,
                'price' => $advert->price,
            ];
        }

        $this->notifyUserOrPivot($user, new PriceChangedNotification($mailData), $email);
    }

    public function notifyMissingAdvert(int $advertId, string $sourceUrl): void
    {
        $advert = Advert::with('users')->find($advertId);
        $users = $advert->users;
        $subject = 'Advert does not exist';

        foreach ($users as $user) {
            $this->notifyUserOrPivot($user, new AdvertMissingNotification($subject, $sourceUrl));
        }
    }

    private function notifyUserOrPivot(User $user, $notification, ?string $pivotEmail = null): void
    {
        if ($pivotEmail) {
            Notification::route('mail', $pivotEmail)->notify($notification);
        } else {
            $user->notify($notification);
        }
    }

}
