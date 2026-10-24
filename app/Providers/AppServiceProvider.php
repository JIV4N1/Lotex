<?php

namespace App\Providers;

use App\Models\Municipality;
use App\Models\SiteSetting;
use App\Models\State;
use App\Models\Zone;
use App\Policies\LocationPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        View::composer('layouts.public', function ($view) {
            $view->with('siteSettings', request()->routeIs('prototype.*') ? SiteSetting::defaults() : SiteSetting::current());
        });
        Gate::policy(State::class, LocationPolicy::class);
        Gate::policy(Municipality::class, LocationPolicy::class);
        Gate::policy(Zone::class, LocationPolicy::class);
    }
}
