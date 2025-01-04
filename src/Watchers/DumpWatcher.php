<?php

namespace Omisteck\Peek\Watchers;

use Omisteck\Peek\DumpRecorder\DumpRecorder;
use Omisteck\Peek\Settings\Settings;

class DumpWatcher extends Watcher
{
    public function register(): void
    {
        $settings = app(Settings::class);

        $this->enabled = $settings->send_dumps_to_peek;

        app(DumpRecorder::class)->register();
    }
}
