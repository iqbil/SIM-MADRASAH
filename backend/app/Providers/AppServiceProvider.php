<?php

namespace App\Providers;

use App\Auth\JwtGuard;
use App\Models\User;
use App\Services\GuardedMigrator;
use App\Services\JwtTokenService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->extend('migrator', function (Migrator $current, Application $app): GuardedMigrator {
            $guarded = new GuardedMigrator($app['migration.repository'], $app['db'], $app['files'], $app['events']);
            foreach ($current->paths() as $path) {
                $guarded->path($path);
            }
            $guarded->setConnection($current->getConnection());

            return $guarded;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Auth::extend('jwt', function (Application $app, string $name, array $config): JwtGuard {
            $guard = new JwtGuard(
                fn (Request $request): ?User => $app->make(JwtTokenService::class)->authenticate($request),
                $app['request'],
                $app['auth']->createUserProvider($config['provider']),
            );
            $app->refresh('request', $guard, 'setRequest');

            return $guard;
        });

        RateLimiter::for('auth.login', fn (Request $request): array => [
            Limit::perMinute(5)->by('login:'.hash('sha256', mb_strtolower(is_string($request->input('login')) ? trim($request->input('login')) : '').'|'.$request->ip())),
            Limit::perMinute(20)->by('login-ip:'.$request->ip()),
        ]);
        RateLimiter::for('auth.refresh', fn (Request $request): Limit => Limit::perMinute(30)->by('refresh:'.$request->ip()));
        RateLimiter::for('auth.google', fn (Request $request): Limit => Limit::perMinute(10)->by('google:'.$request->ip()));
        RateLimiter::for('auth.password', fn (Request $request): Limit => Limit::perMinute(5)->by('password:'.$request->user()->id));
    }
}
