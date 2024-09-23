<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\CheckPriceChangeJob;
use App\Jobs\SendPriceNotificationJob;
use App\Models\Advert;

class MonitorService
{
    private CommonService $service;
    private array $advertData;
    private NotifyPriceService $notifyService;

    public function __construct(CommonService $service, NotifyPriceService $notifyService)
    {
        $this->service = $service;
        $this->notifyService = $notifyService;
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
        $srcFile = $this->service->readSource($sourceUrl);
        $activeAdvert = $this->service->getDataFromFile($srcFile);
        if ($activeAdvert['active']) {
            $this->advertData = $activeAdvert['advertData'];
            $this->dbOperations($advertId);
            $this->service->removeTempFile($srcFile);
        } else {
            $this->notifyService->notifyMissingAdvert($advertId, $sourceUrl);
        }
    }

    protected function dbOperations(int $advertId): void
    {
        $advert = Advert::find($advertId);

        if ($advert->price !== $this->advertData['offers']['price']) {
            $advert->price = $this->advertData['offers']['price'];
            $advert->save();
        }
    }

}
