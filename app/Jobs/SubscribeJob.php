<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use App\Services\SubscribeService;
use Illuminate\Queue\SerializesModels;

class SubscribeJob implements ShouldQueue
{
    use Dispatchable, Queueable, SerializesModels;

    public User $user;
    public string $sourceUrl;
    public ?string $targetEmail = null;

    /**
     * Create a new job instance.
     * @param User $user
     * @param string $sourceUrl
     * @param string|null $targetEmail
     */
    public function __construct(
        User $user,
        string  $sourceUrl,
        ?string $targetEmail = null,
    )
    {
        $this->user = $user;
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
        $service->getPriceProcess($this->user, $this->sourceUrl, $this->targetEmail);
    }
}
