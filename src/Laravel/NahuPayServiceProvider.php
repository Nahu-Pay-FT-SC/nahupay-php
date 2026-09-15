<?php

declare(strict_types=1);

namespace NahuPay\Laravel;

use Illuminate\Support\ServiceProvider;
use NahuPay\NahuPay;

/**
 * Laravel service provider.
 *
 * Auto-discovered via composer.json `extra.laravel.providers`.
 * Manual registration (if needed):
 *
 * ```php
 * // config/app.php
 * 'providers' => [
 *     NahuPay\Laravel\NahuPayServiceProvider::class,
 * ],
 * ```
 *
 * Then publish the config:
 *   php artisan vendor:publish --tag=nahupay
 */
class NahuPayServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/nahupay.php',
            'nahupay'
        );

        $this->app->singleton(NahuPay::class, function ($app) {
            /** @var array{api_key:string,base_url?:string,timeout?:float} $cfg */
            $cfg = $app['config']->get('nahupay');

            return new NahuPay([
                'api_key'  => $cfg['api_key'],
                'base_url' => $cfg['base_url'] ?? null,
                'timeout'  => $cfg['timeout']  ?? 30.0,
            ]);
        });

        // Alias so the facade resolves correctly
        $this->app->alias(NahuPay::class, 'nahupay');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/nahupay.php' => config_path('nahupay.php'),
            ], 'nahupay');
        }
    }
}
