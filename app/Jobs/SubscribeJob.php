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

    /**
     * The number of times the job may be attempted.
     * @var int
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     * @var int
     */
    public int $backoff = 15;

    public User $user;
    public string $sourceUrl;
    public ?string $targetEmail = null;

    /**
     * Create a new job instance.
     * @param User $user
     * @param array $data
     */
    public function __construct(
        User $user,
        array  $data,
    )
    {
        $this->user = $user;
        $this->sourceUrl = $data['url'];
        $this->targetEmail = array_key_exists('email', $data) ? $data['email'] : null;
    }

    /**
     * Execute the job.
     * @param SubscribeService $service
     * @return void
     */
    public function handle(SubscribeService $service): void
    {
        $service->getAdvertProcess($this->user, $this->sourceUrl, $this->targetEmail);
    }
}
