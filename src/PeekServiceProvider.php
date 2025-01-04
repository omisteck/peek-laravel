<?php

namespace Omisteck\Peek;

use Omisteck\Peek\Settings\Settings;
use Omisteck\Peek\Watchers\JobWatcher;
use Omisteck\Peek\Watchers\DumpWatcher;
use Omisteck\Peek\Watchers\ViewWatcher;
use Spatie\LaravelPackageTools\Package;
use Omisteck\Peek\Watchers\CacheWatcher;
use Omisteck\Peek\Watchers\EventWatcher;
use Omisteck\Peek\Watchers\QueryWatcher;
use Omisteck\Peek\Watchers\RequestWatcher;
use Omisteck\Peek\Settings\SettingsFactory;
use Omisteck\Peek\Watchers\ExceptionWatcher;
use Omisteck\Peek\Watchers\SlowQueryWatcher;
use Omisteck\Peek\Watchers\HttpClientWatcher;
use Omisteck\Peek\Watchers\LoggedMailWatcher;
use Omisteck\Peek\Commands\PublishConfigCommand;
use Omisteck\Peek\Watchers\ApplicationLogWatcher;
use Omisteck\Peek\Watchers\DuplicateQueryWatcher;
use Omisteck\Peek\Watchers\DeprecatedNoticeWatcher;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Spatie\LaravelPackageTools\Commands\InstallCommand;

class PeekServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('peek')
            ->hasConfigFile('peek')
            ->hasInstallCommand(function (InstallCommand $command) {
                $command->publishConfigFile();
            });

        $this
            ->registerCommands()
            ->registerSettings()
            ->setProjectName()
            ->registerBindings()
            ->registerWatchers();
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
                'enable' => env('PEEK_ENABLED', ! app()->environment('production')),
                'send_cache_to_peek' => env('SEND_CACHE_TO_PEEK', false),
                'send_dumps_to_peek' => env('SEND_DUMPS_TO_PEEK', true),
                'send_jobs_to_peek' => env('SEND_JOBS_TO_PEEK', false),
                'send_log_calls_to_peek' => env('SEND_LOG_CALLS_TO_PEEK', true),
                'send_queries_to_peek' => env('SEND_QUERIES_TO_PEEK', false),
                'send_duplicate_queries_to_peek' => env('SEND_DUPLICATE_QUERIES_TO_PEEK', false),
                'send_slow_queries_to_peek' => env('SEND_SLOW_QUERIES_TO_PEEK', false),
                'send_requests_to_peek' => env('SEND_REQUESTS_TO_PEEK', false),
                'send_http_client_requests_to_peek' => env('SEND_HTTP_CLIENT_REQUESTS_TO_PEEK', false),
                'send_views_to_peek' => env('SEND_VIEWS_TO_PEEK', false),
                'send_exceptions_to_peek' => env('SEND_EXCEPTIONS_TO_PEEK', true),
                'send_deprecated_notices_to_peek' => env('SEND_DEPRECATED_NOTICES_TO_PEEK', false),
                'send_events_to_peek' => env('SEND_EVENTS_TO_PEEK', false),
            ]);
        });

        return $this;
    }

    public function setProjectName(): self
    {

        if (Peek::$projectName === '') {
            $projectName = config('app.name');

            if ($projectName !== 'Laravel') {
                peek()->project($projectName);
            }
        }

        return $this;
    }

    protected function registerBindings(): self
    {
        $settings = app(Settings::class);

        $this->app->bind(Client::class, function () use ($settings) {
            return new Client($settings->port, $settings->host);
        });

        $this->app->bind(Peek::class, function () {
            $client = app(Client::class);

            $settings = app(Settings::class);

            $peek = new Peek($settings, $client);

            if (! $settings->enable) {
                $peek->disable();
            }

            return $peek;
        });

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
            // DuplicateQueryWatcher::class,
            SlowQueryWatcher::class,
            ViewWatcher::class,
            CacheWatcher::class,
            RequestWatcher::class,
            HttpClientWatcher::class,
            DeprecatedNoticeWatcher::class,
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
            // DuplicateQueryWatcher::class,
            SlowQueryWatcher::class,
            ViewWatcher::class,
            CacheWatcher::class,
            RequestWatcher::class,
            HttpClientWatcher::class,
            DeprecatedNoticeWatcher::class,
        ];

        collect($watchers)
            ->each(function (string $watcherClass) {
                /** @var \Omisteck\Peek\Watchers\Watcher $watcher */
                $watcher = app($watcherClass);

                $watcher->register();
            });

        return $this;
    }
}
