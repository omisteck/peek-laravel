<?php

namespace Tests\Settings;

use Omisteck\Peek\Settings\Settings;
use PHPUnit\Framework\TestCase;

class SettingsTest extends TestCase
{
    public function test_create_with_default_settings()
    {
        $settings = new Settings([]);

        $this->assertTrue($settings->enable);
        $this->assertEquals('localhost', $settings->host);
        $this->assertEquals(44315, $settings->port);
        $this->assertNull($settings->remote_path);
        $this->assertNull($settings->local_path);
        $this->assertFalse($settings->always_send_raw_values);
    }

    public function test_create_with_custom_settings()
    {
        $settings = new Settings([
            'enable' => false,
            'host' => '127.0.0.1',
            'port' => 44315,
        ]);

        $this->assertFalse($settings->enable);
        $this->assertEquals('127.0.0.1', $settings->host);
        $this->assertEquals(44315, $settings->port);
    }

    public function test_dynamic_property_access()
    {
        $settings = new Settings([]);

        $settings->custom_setting = 'test';
        $this->assertEquals('test', $settings->custom_setting);

        $this->assertNull($settings->non_existent_setting);
    }
}
