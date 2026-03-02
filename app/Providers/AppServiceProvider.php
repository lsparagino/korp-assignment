<?php

namespace App\Providers;

use App\Models\User;
use App\Policies\TeamMemberPolicy;
use Carbon\CarbonImmutable;
use Google\Auth\ApplicationDefaultCredentials;
use Google\Auth\Middleware\AuthTokenMiddleware;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('audit.http_client', function (): ?Client {
            $projectId = config('services.google.project_id');

            if (empty($projectId) || app()->runningUnitTests()) {
                return null;
            }

            try {
                $credentials = ApplicationDefaultCredentials::getCredentials(
                    'https://www.googleapis.com/auth/datastore'
                );
                $middleware = new AuthTokenMiddleware($credentials);
                $stack = HandlerStack::create();
                $stack->push($middleware);

                return new Client([
                    'handler' => $stack,
                    'auth' => 'google_auth',
                    'base_uri' => 'https://firestore.googleapis.com/',
                ]);
            } catch (\Throwable $e) {
                Log::error('Firestore HTTP client initialization failed', [
                    'error' => $e->getMessage(),
                ]);

                return null;
            }
        });
    }

    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureRateLimiting();

        Gate::policy(User::class, TeamMemberPolicy::class);
    }

    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null
        );
    }

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            $limit = app()->environment('testing') ? 1000 : 60;

            return Limit::perMinute($limit)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('auth', function (Request $request) {
            $limit = app()->environment('testing') ? 100 : 5;

            return Limit::perMinute($limit)->by($request->ip());
        });
    }
}
