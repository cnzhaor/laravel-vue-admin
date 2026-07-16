<?php

namespace App\Providers;

use Illuminate\Queue\Events\QueueBusy;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(function (QueueBusy $event): void {
            Log::warning('队列积压超过阈值', [
                'connection' => $event->connectionName,
                'queue' => $event->queue,
                'jobs' => $event->size,
            ]);
        });
    }
}
