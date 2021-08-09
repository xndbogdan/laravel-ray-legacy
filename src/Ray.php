<?php

namespace xndbogdan\LaravelRayLegacy;

use Closure;
use Composer\InstalledVersions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\QueryException;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\MailManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Testing\Fakes\MailFake;
use Illuminate\Testing\TestResponse;
use Illuminate\View\View;
use xndbogdan\LaravelRayLegacy\Payloads\EnvironmentPayload;
use xndbogdan\LaravelRayLegacy\Payloads\ExecutedQueryPayload;
use xndbogdan\LaravelRayLegacy\Payloads\LoggedMailPayload;
use xndbogdan\LaravelRayLegacy\Payloads\MailablePayload;
use xndbogdan\LaravelRayLegacy\Payloads\MarkdownPayload;
use xndbogdan\LaravelRayLegacy\Payloads\ModelPayload;
use xndbogdan\LaravelRayLegacy\Payloads\ResponsePayload;
use xndbogdan\LaravelRayLegacy\Payloads\ViewPayload;
use xndbogdan\LaravelRayLegacy\Watchers\CacheWatcher;
use xndbogdan\LaravelRayLegacy\Watchers\EventWatcher;
use xndbogdan\LaravelRayLegacy\Watchers\ExceptionWatcher;
use xndbogdan\LaravelRayLegacy\Watchers\HttpClientWatcher;
use xndbogdan\LaravelRayLegacy\Watchers\JobWatcher;
use xndbogdan\LaravelRayLegacy\Watchers\QueryWatcher;
use xndbogdan\LaravelRayLegacy\Watchers\RequestWatcher;
use xndbogdan\LaravelRayLegacy\Watchers\ViewWatcher;
use xndbogdan\LaravelRayLegacy\Watchers\Watcher;
use Spatie\Ray\Client;
use Spatie\Ray\Payloads\ExceptionPayload;
use Spatie\Ray\Ray as BaseRay;
use Spatie\Ray\Settings\Settings;
use Throwable;

class Ray extends BaseRay
{
    public function __construct(Settings $settings, Client $client = null, string $uuid = null)
    {
        // persist the enabled setting across multiple instantiations
        $enabled = static::$enabled;

        parent::__construct($settings, $client, $uuid);

        static::$enabled = $enabled;
    }

    public function loggedMail(string $loggedMail): self
    {
        $payload = LoggedMailPayload::forLoggedMail($loggedMail);

        $this->sendRequest($payload);

        return $this;
    }

    public function mailable(Mailable ...$mailables): self
    {
        $shouldRestoreFake = false;

        if (get_class(app(MailManager::class)) === MailFake::class) {
            $shouldRestoreFake = true;

            Mail::swap(new MailManager(app()));
        }

        if ($shouldRestoreFake) {
            Mail::fake();
        }

        $payloads = array_map(function (Mailable $mailable) {
            return MailablePayload::forMailable($mailable);
        }, $mailables);

        $this->sendRequest($payloads);

        return $this;
    }

    /**
     * @param Model|iterable ...$model
     *
     * @return \xndbogdan\LaravelRayLegacy\Ray
     */
    public function model(...$model): self
    {
        $models = [];
        foreach ($model as $passedModel) {
            if (is_null($passedModel)) {
                $models[] = null;

                continue;
            }
            if ($passedModel instanceof Model) {
                $models[] = $passedModel;

                continue;
            }

            if (is_iterable($model)) {
                foreach ($passedModel as $item) {
                    $models[] = $item;

                    continue;
                }
            }
        }

        $payloads = array_map(function (?Model $model) {
            return new ModelPayload($model);
        }, $models);

        foreach ($payloads as $payload) {
            ray()->sendRequest($payload);
        }

        return $this;
    }

    /**
     * @param Model|iterable $models
     *
     * @return \xndbogdan\LaravelRayLegacy\Ray
     */
    public function models($models): self
    {
        return $this->model($models);
    }

    public function markdown(string $markdown): self
    {
        $payload = new MarkdownPayload($markdown);

        $this->sendRequest($payload);

        return $this;
    }

    /**
     * @param string[]|array|null $onlyShowNames
     * @param string|null $filename
     *
     * @return \xndbogdan\LaravelRayLegacy\Ray
     */
    public function env(?array $onlyShowNames = null, ?string $filename = null): self
    {
        $filename = $filename ?? app()->environmentFilePath();

        $payload = new EnvironmentPayload($onlyShowNames, $filename);

        $this->sendRequest($payload);

        return $this;
    }

    /**
     * @param null $callable
     *
     * @return \xndbogdan\LaravelRayLegacy\Ray
     */
    public function showEvents($callable = null)
    {
        $watcher = app(EventWatcher::class);

        return $this->handleWatcherCallable($watcher, $callable);
    }

    public function events($callable = null)
    {
        return $this->showEvents($callable);
    }

    public function stopShowingEvents(): self
    {
        /** @var \xndbogdan\LaravelRayLegacy\Watchers\EventWatcher $eventWatcher */
        $eventWatcher = app(EventWatcher::class);

        $eventWatcher->disable();

        return $this;
    }

    public function showExceptions(): self
    {
        /** @var \xndbogdan\LaravelRayLegacy\Watchers\ExceptionWatcher $exceptionWatcher */
        $exceptionWatcher = app(ExceptionWatcher::class);

        $exceptionWatcher->enable();

        return $this;
    }

    public function stopShowingExceptions(): self
    {
        /** @var \xndbogdan\LaravelRayLegacy\Watchers\ExceptionWatcher $exceptionWatcher */
        $exceptionWatcher = app(ExceptionWatcher::class);

        $exceptionWatcher->disable();

        return $this;
    }

    /**
     * @param null $callable
     *
     * @return \xndbogdan\LaravelRayLegacy\Ray
     */
    public function showJobs($callable = null)
    {
        $watcher = app(JobWatcher::class);

        return $this->handleWatcherCallable($watcher, $callable);
    }

    /**
     * @param null $callable
     *
     * @return \xndbogdan\LaravelRayLegacy\Ray
     */
    public function showCache($callable = null)
    {
        $watcher = app(CacheWatcher::class);

        return $this->handleWatcherCallable($watcher, $callable);
    }

    public function stopShowingCache(): self
    {
        app(CacheWatcher::class)->disable();

        return $this;
    }

    public function jobs($callable = null)
    {
        return $this->showJobs($callable);
    }

    public function stopShowingJobs(): self
    {
        app(JobWatcher::class)->disable();

        return $this;
    }

    public function view(View $view): self
    {
        $payload = new ViewPayload($view);

        return $this->sendRequest($payload);
    }

    /**
     * @param null $callable
     *
     * @return \xndbogdan\LaravelRayLegacy\Ray
     */
    public function showViews($callable = null)
    {
        $watcher = app(ViewWatcher::class);

        return $this->handleWatcherCallable($watcher, $callable);
    }

    public function views($callable = null)
    {
        return $this->showViews($callable);
    }

    public function stopShowingViews(): self
    {
        app(ViewWatcher::class)->disable();

        return $this;
    }

    /**
     * @param null $callable
     *
     * @return \xndbogdan\LaravelRayLegacy\Ray
     */
    public function showQueries($callable = null)
    {
        $watcher = app(QueryWatcher::class);

        return $this->handleWatcherCallable($watcher, $callable);
    }

    public function countQueries(callable $callable)
    {
        /** @var QueryWatcher $watcher */
        $watcher = app(QueryWatcher::class);

        $watcher->keepExecutedQueries();

        if (! $watcher->enabled()) {
            $watcher->doNotSendIndividualQueries();
        }

        $this->handleWatcherCallable($watcher, $callable);

        $executedQueryStatistics = collect($watcher->getExecutedQueries())

            ->pipe(function (Collection $queries) {
                return [
                    'Count' => $queries->count(),
                    'Total time' => $queries->sum(function (QueryExecuted $query) {
                        return $query->time;
                    }),
                ];
            });

        $executedQueryStatistics['Total time'] .= ' ms';

        $watcher
            ->stopKeepingAndClearExecutedQueries()
            ->sendIndividualQueries();

        $this->table($executedQueryStatistics, 'Queries');
    }

    public function queries($callable = null)
    {
        return $this->showQueries($callable);
    }

    public function stopShowingQueries(): self
    {
        app(QueryWatcher::class)->disable();

        return $this;
    }

    /**
     * @param null $callable
     *
     * @return \xndbogdan\LaravelRayLegacy\Ray
     */
    public function showRequests($callable = null)
    {
        $watcher = app(RequestWatcher::class);

        return $this->handleWatcherCallable($watcher, $callable);
    }

    public function requests($callable = null)
    {
        return $this->showRequests($callable);
    }

    public function stopShowingRequests(): self
    {
        $this->requestWatcher()->disable();

        return $this;
    }

    /**
     * @param null $callable
     *
     * @return \xndbogdan\LaravelRayLegacy\Ray
     */
    public function showHttpClientRequests($callable = null)
    {
        if (! HttpClientWatcher::supportedByLaravelVersion()) {
            $this->send("Http logging is not available in your Laravel version")->red();

            return $this;
        }

        $watcher = app(HttpClientWatcher::class);

        return $this->handleWatcherCallable($watcher, $callable);
    }

    public function httpClientRequests($callable = null)
    {
        return $this->showHttpClientRequests($callable);
    }

    public function stopShowingHttpClientRequests(): self
    {
        app(HttpClientWatcher::class)->disable();

        return $this;
    }

    protected function handleWatcherCallable(Watcher $watcher, Closure $callable = null): RayProxy
    {
        $rayProxy = new RayProxy();

        $wasEnabled = $watcher->enabled();

        $watcher->enable();

        if ($rayProxy) {
            $watcher->setRayProxy($rayProxy);
        }

        if ($callable) {
            $callable();

            if (! $wasEnabled) {
                $watcher->disable();
            }
        }

        return $rayProxy;
    }

    public function testResponse(TestResponse $testResponse)
    {
        $payload = ResponsePayload::fromTestResponse($testResponse);

        $this->sendRequest($payload);
    }

    protected function requestWatcher(): RequestWatcher
    {
        return app(RequestWatcher::class);
    }

    public function exception(Throwable $exception, array $meta = [])
    {
        $payloads[] = new ExceptionPayload($exception, $meta);

        if ($exception instanceof QueryException) {
            $executedQuery = new QueryExecuted($exception->getSql(), $exception->getBindings(), null, DB::connection(config('database.default')));

            $payloads[] = new ExecutedQueryPayload($executedQuery);
        }

        $this->sendRequest($payloads)->red();

        return $this;
    }

    /**
     * @param \Spatie\Ray\Payloads\Payload|\Spatie\Ray\Payloads\Payload[] $payloads
     * @param array $meta
     *
     * @return \Spatie\Ray\Ray
     * @throws \Exception
     */
    public function sendRequest($payloads, array $meta = []): BaseRay
    {
        if (! $this->enabled()) {
            return $this;
        }

        $meta = [
            'laravel_version' => app()->version(),
        ];

        if (class_exists(InstalledVersions::class)) {
            try {
                $meta['laravel_ray_package_version'] = InstalledVersions::getVersion('spatie/laravel-ray');
            } catch (\Exception $e) {
                $meta['laravel_ray_package_version'] = '0.0.0';
            }
        }

        return BaseRay::sendRequest($payloads, $meta);
    }
}
