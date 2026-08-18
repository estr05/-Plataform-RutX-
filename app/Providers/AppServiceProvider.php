<?php

namespace App\Providers;

use App\Models\User;
use App\Services\RutxHubClient;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Frontera HTTP central hacia el Hub/Relay (servicio único).
        $this->app->singleton(
            RutxHubClient::class,
            fn (): RutxHubClient => new RutxHubClient(config('rutx'))
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

        // Rate limiting del login (P0): 5 intentos por minuto, clave correo+IP.
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)->by(
            strtolower((string) $request->input('email')).'|'.$request->ip()
        ));
    }
}
