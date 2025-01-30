<?php

namespace App\Console\Commands;

use App\Services\MonitorService;
use Illuminate\Console\Command;

class StartEmulation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'start:emulation';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Emulate scheduler one time';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        (new MonitorService())->createCheckingJobs();
    }
}
