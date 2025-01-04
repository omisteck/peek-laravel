<?php

namespace Omisteck\Peek\Watchers;

use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Str;
use Omisteck\Peek\Settings\Settings;

class DeprecatedNoticeWatcher extends Watcher
{
    public function register(): void
    {
        //
    }

    public function concernsDeprecatedNotice(MessageLogged $messageLogged): bool
    {
        $settings = app(Settings::class);
        $this->enabled = $settings->send_deprecated_notices_to_peek;

        if ($this->enabled()) {
            return false;
        }

        return Str::contains($messageLogged->message, ['deprecated', 'Deprecated', '[\ReturnTypeWillChange]']);
    }
}
