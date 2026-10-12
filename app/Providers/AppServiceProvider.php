<?php

namespace App\Providers;

use App\Models\Municipality;
use App\Models\State;
use App\Models\Zone;
use App\Policies\LocationPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Gate::policy(State::class, LocationPolicy::class);
        Gate::policy(Municipality::class, LocationPolicy::class);
        Gate::policy(Zone::class, LocationPolicy::class);
    }
}
