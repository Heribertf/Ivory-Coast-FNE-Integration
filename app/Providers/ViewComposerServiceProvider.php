<?php

namespace App\Providers;

use App\Services\FneApiService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

class ViewComposerServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot(FneApiService $fneService)
    {
        // Share API status with all views
        View::composer('*', function ($view) use ($fneService) {
            $apiStatus = $fneService->checkHealth();
            $balance = $fneService->getStickerBalance();

            $view->with([
                'apiStatus' => $apiStatus,
                'stickerBalance' => $balance['success'] ? $balance['balance'] : null,
                'balanceError' => $balance['success'] ? null : $balance['error']
            ]);
        });
    }
}