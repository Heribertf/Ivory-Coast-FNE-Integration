<?php

namespace App\Providers;

use App\Services\FneApiService;
use Illuminate\Support\ServiceProvider;

class FneServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(FneApiService::class, function ($app) {
            return new FneApiService();
        });
    }

    public function boot()
    {
        // Share common FNE data with all views
        view()->composer('*', function ($view) {
            if (auth()->check()) {
                $fneService = app(FneApiService::class);
                $balance = cache()->remember('fne_sticker_balance', now()->addHours(1), function () use ($fneService) {
                    $result = $fneService->getStickerBalance();
                    return $result['success'] ? $result['balance'] : null;
                });

                $view->with('fneStickerBalance', $balance);
            }
        });
    }
}