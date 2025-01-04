<?php

namespace Omisteck\Peek\Watchers;

use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Omisteck\Peek\Payloads\ApplicationLogPayload;
use Omisteck\Peek\Peek;

class ApplicationLogWatcher extends Watcher
{
    public function register(): void
    {
        /** @var \Omisteck\Peek\Peek $peek */
        $peek = app(Peek::class);

        $this->enabled = $peek->settings->send_log_calls_to_peek;

        Event::listen(MessageLogged::class, function (MessageLogged $message) {
            if (! $this->shouldLogMessage($message)) {
                return;
            }

            if (! class_exists('Omisteck\Peek\Payloads\ApplicationLogPayload')) {
                return;
            }

            $payload = new ApplicationLogPayload($message->message, $message->context);

            /** @var Peek $peek */
            $peek = app(Peek::class);

            switch ($message->level) {
                case 'error':
                case 'critical':
                case 'alert':
                case 'emergency':
                    $peek->error()->send($payload->getContent());

                    break;
                case 'warning':
                    $peek->warning()->send($payload->getContent());

                    break;
            }
        });
    }

    protected function shouldLogMessage(MessageLogged $message): bool
    {
        if (! $this->enabled()) {
            return false;
        }

        if (is_null($message->message)) {
            return false;
        }

        /** @var Peek $peek */
        $peek = app(Peek::class);

        if (! $peek->settings->send_log_calls_to_peek) {
            return false;
        }

        if ((new ExceptionWatcher)->concernsException($message)) {
            return false;
        }

        if ((new LoggedMailWatcher)->concernsLoggedMail($message)) {
            return false;
        }

        if ((new DeprecatedNoticeWatcher)->concernsDeprecatedNotice($message)) {
            return false;
        }

        return true;
    }
}
