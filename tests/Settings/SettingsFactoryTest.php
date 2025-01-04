<?php

namespace Tests\Settings;

use Omisteck\Peek\Settings\SettingsFactory;
use PHPUnit\Framework\TestCase;

class SettingsFactoryTest extends TestCase
{
    public function test_create_from_array()
    {
        $settings = SettingsFactory::createFromArray([
            'enable' => false,
            'host' => '127.0.0.1',
        ]);

        $this->assertFalse($settings->enable);
        $this->assertEquals('127.0.0.1', $settings->host);
    }

    public function test_create_from_config_file()
    {
        // Create a temporary config file
        $configContent = <<<'PHP'
        <?php
        return [
            'enable' => false,
            'host' => '127.0.0.1',
            'port' => 12345,
        ];
        PHP;

        $tempFile = tempnam(sys_get_temp_dir(), 'ray_');
        file_put_contents($tempFile, $configContent);

        $settings = SettingsFactory::createFromConfigFile(dirname($tempFile));

        unlink($tempFile);

        $this->assertNotNull($settings);
    }
}
