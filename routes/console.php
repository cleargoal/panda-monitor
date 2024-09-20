<?php

declare(strict_types=1);

use App\Services\MonitorService;
use Illuminate\Support\Facades\Schedule;

Schedule::call(new MonitorService())->dailyAt('2:00');
