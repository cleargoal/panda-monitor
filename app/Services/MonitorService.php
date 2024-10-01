<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\CheckPriceChangeJob;
use App\Jobs\InitPriceCheckingJob;
use App\Models\Advert;

class MonitorService
{
    private ParserService $parser;
    private NotifyService $notifier;

    public function __construct(ParserService $parser, NotifyService $notifier)
    {
        $this->parser = $parser;
        $this->notifier = $notifier;
    }

    public function createCheckingJobs(): void
    {
        $adverts = Advert::get();
        foreach ($adverts as $advert) {
            CheckPriceChangeJob::dispatch($advert->id, $advert->url);
        }

        InitPriceCheckingJob::dispatch(); // for starting prepare and sending notifications
    }

    public function checkPrices(int $advertId, string $sourceUrl): void
    {
        $advertData = $this->parser->getDataFromPage($sourceUrl);
        if ($advertData['price']) {
            $this->updatePrice($advertId, $advertData['price']);
        } else {
            $this->notifier->notifyMissingAdvert($advertId, $sourceUrl);
        }
    }

    protected function updatePrice(int $advertId, int $newPrice): void
    {
        $advert = Advert::findOrFail($advertId);

        if ($advert && $advert->price !== $newPrice) {
            $advert->price = $newPrice;
            $advert->save();
        }
    }

    /**
     * Method called from Job: InitPriceCheckingJob,
     * works with updated adverts data
     * and calls notifyUserOfChanges
     * @return void
     */
    public function processPrices(): void
    {
        $changedPrices = Advert::with(['users' => function ($query) {
            $query->withPivot('email');
        }])->whereDate('updated_at', today())
            ->whereDate('created_at', '!=', today())
            ->get();

        $userAdverts = [];

        foreach ($changedPrices as $advert) {
            foreach ($advert->users as $user) {
                $email = $user->pivot->email ?: $user->email;
                $userAdverts[$email]['user'] = $user;
                $userAdverts[$email]['adverts'][] = $advert;
            }
        }

        $this->notifier->prepareChanged($userAdverts);
    }
}
