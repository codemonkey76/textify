<?php

namespace App\Providers;

use App\Services\ClickSendApi;
use Illuminate\Support\ServiceProvider;

class ClickSendApiServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(ClickSendApi::class, function () {
            return new ClickSendApi(
                config('services.clicksend.username'),
                config('services.clicksend.api_key'),
                config('services.clicksend.base_url')
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
