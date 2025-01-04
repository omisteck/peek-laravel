<?php

namespace Tests\Settings;

use PHPUnit\Framework\TestCase;
use Omisteck\Peek\Settings\Settings;
use Omisteck\Peek\Settings\SettingsFactory;

class SettingsTest extends TestCase
{
    public function testCreateWithDefaultSettings()
    {
        $settings = new Settings([]);

        $this->assertTrue($settings->enable);
        $this->assertEquals('localhost', $settings->host);
        $this->assertEquals(44315, $settings->port);
        $this->assertNull($settings->remote_path);
        $this->assertNull($settings->local_path);
        $this->assertFalse($settings->always_send_raw_values);
    }

    public function testCreateWithCustomSettings()
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

    public function testDynamicPropertyAccess()
    {
        $settings = new Settings([]);

        $settings->custom_setting = 'test';
        $this->assertEquals('test', $settings->custom_setting);

        $this->assertNull($settings->non_existent_setting);
    }
}
