<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\CheckPriceChangeJob;
use App\Jobs\SendPriceNotificationJob;
use App\Models\Advert;
use Illuminate\Support\Facades\Log;

class MonitorService
{
    private ParserService $service;
    private array $advertData;
    private NotifyService $notifyService;

    public function __construct()
    {
        $this->service = new ParserService;
        $this->notifyService = new NotifyService;
    }

    public function createCheckingJobs(): void
    {
        $adverts = Advert::get();
        foreach ($adverts as $advert) {
            CheckPriceChangeJob::dispatch($advert->id, $advert->url);
        }
        SendPriceNotificationJob::dispatch(); // for starting prepare and sending notifications
    }

    public function checkPrices(int $advertId, string $sourceUrl): void
    {
        $this->advertData = $this->service->getDataFromPage($sourceUrl);
        if ($this->advertData['price']) {
            $this->dbOperations($advertId);
        } else {
            $this->notifyService->notifyMissingAdvert($advertId, $sourceUrl);
        }
    }

    protected function dbOperations(int $advertId): void
    {
        $advert = Advert::find($advertId);

        if ($advert->price !== $this->advertData['price']) {
            $advert->price = $this->advertData['price'];
            $advert->save();
        }
    }

    /**
     * Method called from Job: SendPriceNotificationJob,
     * works with updated adverts data
     * and calls notifyUserOfChanges
     * @return void
     */
    public function processPrices(): void
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
                $this->notifyService->notifyUserOfChanges($userData['user'], $userData['adverts']);
            }
        }
    }

}
