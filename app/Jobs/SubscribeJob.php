<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use App\Services\SubscribeService;
use Illuminate\Queue\SerializesModels;

class SubscribeJob implements ShouldQueue
{
    use Dispatchable, Queueable, SerializesModels;

    public int $userId;
    public string $sourceUrl;
    public ?string $targetEmail = null;

    /**
     * Create a new job instance.
     * @param int $userId
     * @param string $sourceUrl
     * @param string|null $targetEmail
     */
    public function __construct(
        int     $userId,
        string  $sourceUrl,
        ?string $targetEmail = null,
    )
    {
        $this->userId = $userId;
        $this->sourceUrl = $sourceUrl;
        $this->targetEmail = $targetEmail;
    }

    /**
     * Execute the job.
     * @param SubscribeService $service
     * @return void
     */
    public function handle(SubscribeService $service): void
    {
        $service->getPriceProcess($this->userId, $this->sourceUrl, $this->targetEmail);
    }
}
