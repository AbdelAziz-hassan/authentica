<?php

namespace AbdelAzizHassan\Authentica;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use AbdelAzizHassan\Authentica\Contracts\AuthenticaClient;
use AbdelAzizHassan\Authentica\Services\AuthenticaClientImpl;
use AbdelAzizHassan\Authentica\Http\Middleware\EnsureJsonResponses;
use AbdelAzizHassan\Authentica\Support\CircuitBreaker;
use AbdelAzizHassan\Authentica\Support\CircuitStoreFactory;
use AbdelAzizHassan\Authentica\Http\Controllers\Webhooks\NafathWebhookController;

class AuthenticaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/authentica.php', 'authentica');

        // Bind CircuitBreaker (no-op when disabled)
        $this->app->singleton(CircuitBreaker::class, function () {
            $cfg = (array) config('authentica.circuit', []);

            if (empty($cfg['enabled'])) {
                // no-op breaker
                return new class {
                    public function state(): string { return 'closed'; }
                    public function allow(): bool { return true; }
                    public function recordSuccess(): void {}
                    public function recordFailure(): void {}
                };
            }

            $store = CircuitStoreFactory::make($cfg);
            return CircuitBreaker::makeFromConfig($store, $cfg);
        });

        // Bind the client
        $this->app->bind(AuthenticaClient::class, function () {
            return new AuthenticaClientImpl(config('authentica'));
        });
    }

    public function boot(): void
    {
        // Publish config
        $this->publishes([
            __DIR__ . '/../config/authentica.php' => config_path('authentica.php'),
        ], 'authentica-config');

        // Middleware alias
        /** @var Router $router */
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('authentica.json', EnsureJsonResponses::class);

        // Auto route for Nafath webhook
        if (config('authentica.webhooks.nafath.enabled')) {
            $path = trim(config('authentica.webhooks.nafath.path', '/webhooks/authentica/nafath'), '/');

            $router->middleware(['api', 'authentica.json'])
                   ->post("/{$path}", [NafathWebhookController::class, '__invoke'])
                   ->name('authentica.webhooks.nafath');
        }
    }
}
