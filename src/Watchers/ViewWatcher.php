<?php

namespace Omisteck\Peek\Watchers;

use Illuminate\Support\Facades\Event;
use Omisteck\Peek\Peek;
use Omisteck\Peek\Settings\Settings;

class ViewWatcher extends Watcher
{
    public function register(): void
    {
        $settings = app(Settings::class);

        $this->enabled = $settings->send_views_to_peek;

        Event::listen('composing:*', function ($event, $data) {
            if (! $this->enabled()) {
                return;
            }

            /** @var \Illuminate\View\View $view */
            $view = $data[0];

            $peek = app(Peek::class)->view($view);

            optional($this->peekProxy)->applyCalledMethods($peek);
        });
    }
}
