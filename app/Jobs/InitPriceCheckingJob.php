<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\MonitorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class InitPriceCheckingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Execute the job.
     *
     * @param MonitorService $monitorService
     * @return void
     */
    public function handle(MonitorService $monitorService): void
    {
        $monitorService->processPrices();
    }
}
