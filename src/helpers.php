<?php

use Illuminate\Contracts\Container\BindingResolutionException;
use Omisteck\Peek\BasePeek;
use Omisteck\Peek\Peek as LaravelPeek;
use Omisteck\Peek\Settings\SettingsFactory;

if (! function_exists('peek')) {
    /**
     * @param  mixed  ...$args
     * @return \Omisteck\Peek\BasePeek|LaravelPeek
     */
    function peek(...$args)
    {
        if (class_exists(LaravelPeek::class)) {
            try {
                return app(LaravelPeek::class)->send(...$args);
            } catch (BindingResolutionException $exception) {
            }
        }

        $peekClass = BasePeek::class;

        $settings = SettingsFactory::createFromConfigFile();

        return (new $peekClass($settings))->send(...$args);
    }

    register_shutdown_function(function () {
        peek()->throwExceptions();
    });
}

if (! function_exists('pk')) {
    function pk(...$args)
    {
        peek(...$args)->die();
    }
}
