<?php

namespace App\Providers;

use Aws\TranscribeService\TranscribeServiceClient;
use Illuminate\Support\ServiceProvider;

class TranscriptionServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(TranscribeServiceClient::class, function ($app) {
            return new TranscribeServiceClient([
                'region' => config('services.aws.region', 'ap-southeast-2'),
                'version' => 'latest',
                'credentials' => [
                    'key' => config('services.aws.key'),
                    'secret' => config('services.aws.secret'),
                ],
            ]);
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
