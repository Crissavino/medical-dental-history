<?php

namespace App\Providers;

use App\Models\AnamnesisVersion;
use App\Models\Attachment;
use App\Models\Encounter;
use App\Models\Patient;
use App\Models\Treatment;
use App\Observers\AuditObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        RateLimiter::for('intake', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        Patient::observe(AuditObserver::class);
        AnamnesisVersion::observe(AuditObserver::class);
        Encounter::observe(AuditObserver::class);
        Treatment::observe(AuditObserver::class);
        Attachment::observe(AuditObserver::class);
    }
}
