<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\User;
use App\Services\MonitorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CheckPriceChangeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $advertId;
    protected string $url;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(int $advertId, string $url)
    {
        $this->advertId = $advertId;
        $this->url = $url;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(MonitorService $monitorService): void
    {
        $monitorService->checkPrices($this->advertId, $this->url);
    }
}
