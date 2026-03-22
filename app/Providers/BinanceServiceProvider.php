<?php

namespace App\Providers;

use App\Services\BinanceService;
use Illuminate\Support\ServiceProvider;

class BinanceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(BinanceService::class);
    }
}
