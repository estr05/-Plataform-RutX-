<?php

namespace App\Providers;

use App\Models\User;
use App\Services\RutxApiClient;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Cliente HTTP central hacia el Sincronizador (frontera única).
        $this->app->singleton(
            RutxApiClient::class,
            fn (): RutxApiClient => new RutxApiClient(config('rutx'))
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // RBAC (sprint/2): un Gate por permiso del catálogo, resuelto contra
        // el rol del usuario. Habilita Auth::user()->can(...), el middleware
        // 'can:' y @can() en las vistas con una única fuente de verdad.
        foreach (config('permissions.catalog', []) as $permission) {
            Gate::define($permission, fn (User $user): bool => $user->hasPermission($permission));
        }
    }
}
