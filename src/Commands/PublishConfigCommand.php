<?php

namespace Omisteck\Peek\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class PublishConfigCommand extends Command
{
    protected $signature = 'peek:publish-config {--homestead : Indicates that Homestead is being used}
                                               {--docker : Indicates that Docker is being used}';

    protected $description = 'Create the Peek config file in project root.';

    public function handle()
    {
        if ((new Filesystem())->exists('config/peek.php')) {
            $this->error('peek.php already exists in the project root');

            return;
        }

        copy(__DIR__ . '/../../config/peek.php', base_path('config/peek.php'));

        if ($this->option('docker')) {
            file_put_contents(
                base_path('config/peek.php'),
                str_replace(
                    "'host' => env('PEEK_HOST', 'localhost')",
                    "'host' => env('PEEK_HOST', 'host.docker.internal')",
                    file_get_contents(base_path('peek.php'))
                )
            );
        }

        if ($this->option('homestead')) {
            file_put_contents(
                base_path('peek.php'),
                str_replace(
                    "'host' => env('PEEK_HOST', 'localhost')",
                    "'host' => env('PEEK_HOST', '10.0.2.2')",
                    file_get_contents(base_path('peek.php'))
                )
            );
        }

        $this->info('`peek.php` created in the project base directory');
    }
}
