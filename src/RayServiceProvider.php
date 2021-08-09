<?php

namespace Spatie\LaravelRayLegacy;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Testing\TestResponse;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Spatie\LaravelRayLegacy\Commands\PublishConfigCommand;
use Spatie\LaravelRayLegacy\Payloads\MailablePayload;
use Spatie\LaravelRayLegacy\Payloads\ModelPayload;
use Spatie\LaravelRayLegacy\Payloads\QueryPayload;
use Spatie\LaravelRayLegacy\Polyfills\Stringable;
use Spatie\LaravelRayLegacy\Watchers\ApplicationLogWatcher;
use Spatie\LaravelRayLegacy\Watchers\CacheWatcher;
use Spatie\LaravelRayLegacy\Watchers\DumpWatcher;
use Spatie\LaravelRayLegacy\Watchers\EventWatcher;
use Spatie\LaravelRayLegacy\Watchers\ExceptionWatcher;
use Spatie\LaravelRayLegacy\Watchers\HttpClientWatcher;
use Spatie\LaravelRayLegacy\Watchers\JobWatcher;
use Spatie\LaravelRayLegacy\Watchers\LoggedMailWatcher;
use Spatie\LaravelRayLegacy\Watchers\QueryWatcher;
use Spatie\LaravelRayLegacy\Watchers\RequestWatcher;
use Spatie\LaravelRayLegacy\Watchers\ViewWatcher;
use Spatie\Ray\Client;
use Spatie\Ray\PayloadFactory;
use Spatie\Ray\Payloads\Payload;
use Spatie\Ray\Settings\Settings;
use Spatie\Ray\Settings\SettingsFactory;

class RayServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this
            ->registerCommands()
            ->registerSettings()
            ->registerBindings()
            ->registerWatchers()
            ->registerMacros()
            ->registerBindings()
            ->registerBladeDirectives()
            ->registerPayloadFinder();
    }

    public function boot()
    {
        $this->bootWatchers();
    }

    protected function registerCommands(): self
    {
        $this->commands(PublishConfigCommand::class);

        return $this;
    }

    protected function registerSettings(): self
    {
        $this->app->singleton(Settings::class, function ($app) {
            $settings = SettingsFactory::createFromConfigFile($app->configPath());

            return $settings->setDefaultSettings([
                'enable' => env('RAY_ENABLED', ! app()->environment('production')),
                'send_cache_to_ray' => env('SEND_CACHE_TO_RAY', false),
                'send_dumps_to_ray' => env('SEND_DUMPS_TO_RAY', true),
                'send_jobs_to_ray' => env('SEND_JOBS_TO_RAY', false),
                'send_log_calls_to_ray' => env('SEND_LOG_CALLS_TO_RAY', true),
                'send_queries_to_ray' => env('SEND_QUERIES_TO_RAY', false),
                'send_requests_to_ray' => env('SEND_REQUESTS_TO_RAY', false),
                'send_http_client_requests_to_ray' => env('SEND_HTTP_CLIENT_REQUESTS_TO_RAY', false),
                'send_views_to_ray' => env('SEND_VIEWS_TO_RAY', false),
                'send_exceptions_to_ray' => env('SEND_EXCEPTIONS_TO_RAY', true),
            ]);
        });

        return $this;
    }

    protected function registerBindings(): self
    {
        $settings = app(Settings::class);

        $this->app->bind(Client::class, function () use ($settings) {
            return new Client($settings->port, $settings->host);
        });

        $this->app->bind(Ray::class, function () {
            $client = app(Client::class);

            $settings = app(Settings::class);

            $ray = new Ray($settings, $client);

            if (! $settings->enable) {
                $ray->disable();
            }

            return $ray;
        });

        Payload::$originFactoryClass = OriginFactory::class;

        return $this;
    }

    protected function registerWatchers(): self
    {
        $watchers = [
            ExceptionWatcher::class,
            LoggedMailWatcher::class,
            ApplicationLogWatcher::class,
            JobWatcher::class,
            EventWatcher::class,
            DumpWatcher::class,
            QueryWatcher::class,
            ViewWatcher::class,
            CacheWatcher::class,
            RequestWatcher::class,
            HttpClientWatcher::class,
        ];

        collect($watchers)
            ->each(function (string $watcherClass) {
                $this->app->singleton($watcherClass);
            });

        return $this;
    }

    protected function bootWatchers(): self
    {
        $watchers = [
            ExceptionWatcher::class,
            LoggedMailWatcher::class,
            ApplicationLogWatcher::class,
            JobWatcher::class,
            EventWatcher::class,
            DumpWatcher::class,
            QueryWatcher::class,
            ViewWatcher::class,
            CacheWatcher::class,
            RequestWatcher::class,
            HttpClientWatcher::class,
        ];

        collect($watchers)
            ->each(function (string $watcherClass) {
                /** @var \Spatie\LaravelRayLegacy\Watchers\Watcher $watcher */
                $watcher = app($watcherClass);

                $watcher->register();
            });

        return $this;
    }

    protected function registerMacros(): self
    {
        Collection::macro('ray', function (string $description = '') {
            $description === ''
                ? ray($this->items)
                : ray($description, $this->items);

            return $this;
        });

        TestResponse::macro('ray', function () {
            ray()->testResponse($this);

            return $this;
        });


        Stringable::macro('ray', function (string $description = '') {
            $description === ''
                ? ray($this->value)
                : ray($description, $this->value);

            return $this;
        });


        Builder::macro('ray', function () {
            $payload = new QueryPayload($this);

            ray()->sendRequest($payload);

            return $this;
        });

        return $this;
    }

    protected function registerBladeDirectives(): self
    {
        if (! $this->app->has('blade.compiler')) {
            return $this;
        }

        Blade::directive('ray', function ($expression) {
            return "<?php ray($expression); ?>";
        });

        return $this;
    }

    protected function registerPayloadFinder(): self
    {
        PayloadFactory::registerPayloadFinder(function ($argument) {
            if ($argument instanceof Model) {
                return new ModelPayload($argument);
            }

            if ($argument instanceof Mailable) {
                return MailablePayload::forMailable($argument);
            }

            return null;
        });

        return $this;
    }
}
