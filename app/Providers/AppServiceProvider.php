<?php

namespace App\Providers;

use App\Domain\Tax\Contracts\TaxCalculatorInterface;
use App\Services\TaxCalculatorService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(TaxCalculatorInterface::class, TaxCalculatorService::class);
    }

    public function boot(): void
    {
        //
    }
}
