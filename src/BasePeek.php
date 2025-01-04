<?php

namespace Omisteck\Peek;

use Closure;
use Exception;
use Throwable;
use TypeError;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Str;
use Composer\InstalledVersions;
use Omisteck\Peek\Support\Counters;
use Omisteck\Peek\Support\Limiters;
use Omisteck\Peek\Settings\Settings;
use Omisteck\Peek\Concerns\PeekStatus;
use Omisteck\Peek\Payloads\LogPayload;
use Omisteck\Peek\Support\RateLimiter;
use Omisteck\Peek\Payloads\HtmlPayload;
use Omisteck\Peek\Support\IgnoredValue;
use Omisteck\Peek\Payloads\ImagePayload;
use Omisteck\Peek\Payloads\LabelPayload;
use Omisteck\Peek\Payloads\TablePayload;
use Omisteck\Peek\Payloads\TracePayload;
use Omisteck\Peek\Payloads\CallerPayload;
use Omisteck\Peek\Payloads\CustomPayload;
use Omisteck\Peek\Payloads\NotifyPayload;
use Omisteck\Peek\Payloads\MeasurePayload;
use Omisteck\Peek\Payloads\PhpInfoPayload;
use Omisteck\Peek\Payloads\ShowAppPayload;
use Symfony\Component\Stopwatch\Stopwatch;
use Omisteck\Peek\Payloads\ClearAllPayload;
use Omisteck\Peek\Payloads\ConfettiPayload;
use Omisteck\Peek\Support\ExceptionHandler;
use Omisteck\Peek\Payloads\ExceptionPayload;
use Omisteck\Peek\Payloads\NewScreenPayload;
use Omisteck\Peek\Payloads\CreateLockPayload;
use Omisteck\Peek\Payloads\JsonStringPayload;
use Omisteck\Peek\Payloads\DecodedJsonPayload;
use Omisteck\Peek\Payloads\FileContentsPayload;


class BasePeek
{
    use PeekStatus;
    /** @var \Omisteck\Peek\Settings\Settings */
    public $settings;

    /** @var \Omisteck\Peek\Client */
    protected static $client;

    /** @var \Omisteck\Peek\Support\Counters */
    public static $counters;

    /** @var \Omisteck\Peek\Support\Limiters */
    public static $limiters;

    /** @var string */
    public static $fakeUuid;

    /** @var \Omisteck\Peek\Origin\Origin|null */
    public $limitOrigin = null;

    /** @var string */
    public $uuid = '';

    /** @var bool */
    public $canSendPayload = true;

    /** @var array|\Exception[] */
    public static $caughtExceptions = [];

    /** @var \Symfony\Component\Stopwatch\Stopwatch[] */
    public static $stopWatches = [];

    /** @var bool|null */
    public static $enabled = null;

    /** @var RateLimiter */
    public static $rateLimiter;

    /** @var string */
    public static $projectName = '';

    /** @var Closure|null */
    public static $beforeSendRequest = null;

    public $status;


    public function __construct(Settings $settings, ?Client $client = null, ?string $uuid = null)
    {
        $this->settings = $settings;

        self::$client = $client ?? self::$client ?? new Client($settings->port, $settings->host);

        self::$counters = self::$counters ?? new Counters;

        self::$limiters = self::$limiters ?? new Limiters;

        $this->uuid = $uuid ?? static::$fakeUuid ?? Uuid::uuid4()->toString();

        static::$enabled = static::$enabled ?? $this->settings->enable ?? true;

        static::$rateLimiter = static::$rateLimiter ?? RateLimiter::disabled();
    }

    /**
     * @param  string  $projectName
     * @return $this
     */
    public function project($projectName): self
    {
        static::$projectName = $projectName;

        return $this;
    }

    public function enable(): self
    {
        static::$enabled = true;

        return $this;
    }

    public function disable(): self
    {
        static::$enabled = false;

        return $this;
    }

    public function enabled(): bool
    {
        return static::$enabled || static::$enabled === null;
    }

    public function disabled(): bool
    {
        return static::$enabled === false;
    }

    // public static function useClient(Client $client): void
    // {
    //     self::$client = $client;
    // }

    public function newScreen(string $name = ''): self
    {
        $name = $this->sanitizeNewScreenName($name);

        $payload = new NewScreenPayload($name);

        return $this->sendRequest($payload);
    }

    protected function sanitizeNewScreenName(string $name): string
    {
        if (strpos($name, '__pest_evaluable_') === 0) {
            $name = substr($name, 17);

            $name = str_replace('_', ' ', $name);
        }

        return $name;
    }

    public function clearAll(): self
    {
        $payload = new ClearAllPayload;

        return $this->sendRequest($payload);
    }

    public function clearScreen(): self
    {
        return $this->newScreen();
    }

    /**
     * @param  string|callable  $stopwatchName
     * @return $this
     */
    public function measure($stopwatchName = 'default'): self
    {
        if ($stopwatchName instanceof Closure) {
            return $this->measureClosure($stopwatchName);
        }

        if (! isset(static::$stopWatches[$stopwatchName])) {
            $stopwatch = new Stopwatch(true);
            static::$stopWatches[$stopwatchName] = $stopwatch;

            $event = $stopwatch->start($stopwatchName);
            $payload = new MeasurePayload($stopwatchName, $event);
            $payload->concernsNewTimer();

            return $this->sendRequest($payload);
        }

        $stopwatch = static::$stopWatches[$stopwatchName];
        $event = $stopwatch->lap($stopwatchName);
        $payload = new MeasurePayload($stopwatchName, $event);

        return $this->sendRequest($payload);
    }

    protected function measureClosure(Closure $closure): self
    {
        $stopwatch = new Stopwatch(true);

        $stopwatch->start('closure');

        $closure();

        $event = $stopwatch->stop('closure');

        $payload = new MeasurePayload('closure', $event);

        return $this->sendRequest($payload);
    }

    public function stopTime(string $stopwatchName = ''): self
    {
        if ($stopwatchName === '') {
            static::$stopWatches = [];

            return $this;
        }

        if (isset(static::$stopWatches[$stopwatchName])) {
            unset(static::$stopWatches[$stopwatchName]);

            return $this;
        }

        return $this;
    }

    public function notify(string $text): self
    {
        $payload = new NotifyPayload($text);

        return $this->sendRequest($payload);
    }

    /**
     * Sends the provided value(s) encoded as a JSON string using json_encode().
     */
    public function toJson(...$values): self
    {
        $payloads = array_map(function ($value) {
            return new JsonStringPayload($value);
        }, $values);

        return $this->sendRequest($payloads);
    }

    /**
     * Sends the provided JSON string(s) decoded using json_decode().
     */
    public function json(string ...$jsons): self
    {
        $payloads = array_map(function ($json) {
            return new DecodedJsonPayload($json);
        }, $jsons);

        return $this->sendRequest($payloads);
    }

    public function file(string $filename): self
    {
        $payload = new FileContentsPayload($filename);

        return $this->sendRequest($payload);
    }

    public function image(string $location): self
    {
        $payload = new ImagePayload($location);

        return $this->sendRequest($payload);
    }

    public function die($status = ''): void
    {
        exit($status);
    }

    public function className(object $object): self
    {
        return $this->send(get_class($object));
    }

    public function phpinfo(string ...$properties): self
    {
        $payload = new PhpInfoPayload(...$properties);

        return $this->sendRequest($payload);
    }

    public function trace(?Closure $startingFromFrame = null): self
    {
        // Get the raw backtrace
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT);

        $payload = new TracePayload(array_values($backtrace));

        return $this->sendRequest($payload);
    }

    public function backtrace(?Closure $startingFromFrame = null): self
    {
        return $this->trace($startingFromFrame);
    }

    public function caller(): self
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT);

        // Get the first non-internal call
        $caller = collect($backtrace)
            ->first(function ($frame) {
                return isset($frame['file']) &&
                    ! str_contains($frame['file'], 'vendor/omisteck/peek');
            });

        return $this->sendRequest(new CallerPayload([$caller]));
    }

    public function table(array $values, $label = 'Table'): self
    {
        $payload = new TablePayload($values, $label);

        return $this->sendRequest($payload);
    }

    public function count(?string $name = null): self
    {
        $fingerPrint = (new \Omisteck\Peek\Origin\DefaultOriginFactory)->getOrigin()->fingerPrint();

        [$peek, $times] = self::$counters->increment($name ?? $fingerPrint);

        $message = 'Called ';

        if ($name) {
            $message .= "`{$name}` ";
        }

        $message .= "{$times} ";

        $message .= $times === 1
            ? 'time'
            : 'times';

        $message .= '.';

        $peek->sendCustom($message, 'Count');

        return $peek;
    }

    public function sendCustom(string $content, string $label = ''): self
    {
        $customPayload = new CustomPayload($content, $label);

        return $this->sendRequest($customPayload);
    }

    public function clearCounters(): self
    {
        self::$counters->clear();

        return $this;
    }

    public function counterValue(string $name): int
    {
        return self::$counters->get($name);
    }

    public function label(string $label): self
    {
        $payload = new LabelPayload($label);

        return $this->sendRequest($payload);
    }

    // public function pause(): self
    // {
    //     $lockName = md5(time());

    //     $payload = new CreateLockPayload($lockName);

    //     $this->sendRequest($payload);

    //     do {
    //         sleep(1);
    //     } while (self::$client->lockExists($lockName));

    //     return $this;
    // }

    public function url(string $url, string $label = ''): self
    {
        if (! Str::startsWith($url, 'http')) {
            $url = "https://{$url}";
        }

        if (empty($label)) {
            $label = $url;
        }

        $link = "<a href='{$url}'>{$label}</a>";

        return $this->html($link);
    }

    public function link(string $url, string $label = '')
    {
        return $this->url($url, $label);
    }

    public function html(string $html = ''): self
    {
        $payload = new HtmlPayload($html);

        return $this->sendRequest($payload);
    }

    public function confetti(): self
    {
        return $this->sendRequest(new ConfettiPayload);
    }

    public function exception(Throwable $exception, array $meta = [])
    {
        $payload = new ExceptionPayload($exception, $meta);

        $this->sendRequest($payload);

        $this->error();

        return $this;
    }

    /**
     * @param  callable|string|null  $callback
     */
    public function catch($callback = null): self
    {
        $result = (new ExceptionHandler)->catch($this, $callback);

        if ($result instanceof Peek) {
            return $result;
        }

        return $this;
    }

    public function throwExceptions(): self
    {
        while (! empty(self::$caughtExceptions)) {
            throw array_shift(self::$caughtExceptions);
        }

        return $this;
    }

    // public function text(string $text): self
    // {
    //     $payload = new TextPayload($text);

    //     return $this->sendRequest($payload);
    // }

    public function raw(...$arguments): self
    {
        if (! count($arguments)) {
            return $this;
        }

        $payloads = array_map(function ($argument) {
            return LogPayload::createForArguments([$argument]);
        }, $arguments);

        return $this->sendRequest($payloads);
    }

    public function send(...$arguments): self
    {
        if (! count($arguments)) {
            return $this;
        }

        if ($this->settings->always_send_raw_values) {
            return $this->raw(...$arguments);
        }

        // map through the arguments to check if they are a string or a closure
        $arguments = array_map(function ($argument) {
            // check if the argument is a string
            if (is_string($argument)) {
                return $argument;
            }

            if (! $argument instanceof Closure) {
                return $argument;
            }

            // if the argument is a closure, we need to execute it
            try {
                $result = $argument($this);

                // use a specific class we can filter out instead of null so that null
                // payloads can still be sent.
                return $result instanceof Peek ? IgnoredValue::make() : $result;
            } catch (Exception $exception) {
                self::$caughtExceptions[] = $exception;

                return IgnoredValue::make();
            } catch (TypeError $error) {
                return $argument;
            }
        }, $arguments);

        // filter out the ignored values
        $arguments = array_filter($arguments, function ($argument) {
            return ! $argument instanceof IgnoredValue;
        });

        if (empty($arguments)) {
            return $this;
        }

        $payloads = PayloadFactory::createForValues($arguments);

        return $this->sendRequest($payloads);
    }

    public function pass($argument)
    {
        $this->send($argument);

        return $argument;
    }

    public function showApp(): self
    {
        $payload = new ShowAppPayload;

        return $this->sendRequest($payload);
    }

    /**
     * @param  \Omisteck\Peek\Payloads\Payload|\Omisteck\Peek\Payloads\Payload[]  $payloads
     * @return $this
     *
     * @throws \Exception
     */
    public function sendRequest($payloads, array $meta = []): self
    {
        if (! $this->enabled()) {
            return $this;
        }

        if (empty($payloads)) {
            return $this;
        }

        if (! $this->canSendPayload) {
            return $this;
        }

        if (! empty($this->limitOrigin)) {
            if (! self::$limiters->canSendPayload($this->limitOrigin)) {
                return $this;
            }

            self::$limiters->increment($this->limitOrigin);
        }

        if (! is_array($payloads)) {
            $payloads = [$payloads];
        }

        try {
            if (class_exists(InstalledVersions::class)) {
                $meta['peek_package_version'] = InstalledVersions::getVersion('omisteck/peek');
            }
        } catch (Exception $e) {
        }


        if (
            self::rateLimiter()->isMaxReached() ||
            self::rateLimiter()->isMaxPerSecondReached()
        ) {
            $this->notifyWhenRateLimitReached();

            return $this;
        }

        $allMeta = array_merge([
            'php_version' => phpversion(),
            'php_version_id' => PHP_VERSION_ID,
            'project_name' => static::$projectName,
        ], $meta);

        if ($closure = static::$beforeSendRequest) {
            $closure($payloads, $allMeta);
        }

        foreach ($payloads as $payload) {
            $payload->remotePath = $this->settings->remote_path;
            $payload->localPath = $this->settings->local_path;
            $payload->status = $this->status;
        }

        $request = new Request($this->uuid, $payloads, $allMeta);

        self::$client->send($request);

        self::rateLimiter()->hit();

        return $this;
    }

    public function status(string $status): self
    {
        $this->status = $status;

        return $this;
    }


    // public static function makePathOsSafe(string $path): string
    // {
    //     return str_replace('/', DIRECTORY_SEPARATOR, $path);
    // }

    public static function rateLimiter(): RateLimiter
    {
        return self::$rateLimiter;
    }

    protected function notifyWhenRateLimitReached(): void
    {
        if (self::rateLimiter()->isNotified()) {
            return;
        }

        $customPayload = new CustomPayload('Rate limit has been reached...', 'Rate limit');

        $request = new Request($this->uuid, [$customPayload], []);

        self::$client->send($request);

        self::rateLimiter()->notify();
    }

    public static function beforeSendRequest(?Closure $closure = null): void
    {
        static::$beforeSendRequest = $closure;
    }
}
