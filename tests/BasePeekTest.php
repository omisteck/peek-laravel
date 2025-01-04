<?php

namespace Omisteck\Peek\Tests;

use Exception;
use Mockery;
use Omisteck\Peek\BasePeek;
use Omisteck\Peek\Client;
use Omisteck\Peek\Settings\Settings;
use Omisteck\Peek\Settings\SettingsFactory;
use Omisteck\Peek\Support\RateLimiter;
use PHPUnit\Framework\TestCase;

class BasePeekTest extends TestCase
{
    protected BasePeek $peek;

    protected Settings $settings;

    protected $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->settings = SettingsFactory::createFromArray([
            'enable' => true,
            'host' => 'localhost',
            'port' => 44315,
            'remote_path' => null,
            'local_path' => null,
            'always_send_raw_values' => false,
        ]);

        $this->client = Mockery::mock(Client::class);
        $this->peek = new BasePeek($this->settings, $this->client);

        // Reset static properties
        BasePeek::$enabled = null;
        BasePeek::$projectName = '';
        BasePeek::$rateLimiter = RateLimiter::disabled();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_basic_send()
    {
        $this->client->shouldReceive('send')->once();
        $result = $this->peek->send('test');
        $this->assertSame($this->peek, $result);
    }

    public function test_send_with_multiple_arguments()
    {
        $this->client->shouldReceive('send')->once();
        $result = $this->peek->send('test1', 'test2', ['test3']);
        $this->assertSame($this->peek, $result);
    }

    public function test_raw_send()
    {
        $this->client->shouldReceive('send')->once();
        $result = $this->peek->raw('test');
        $this->assertSame($this->peek, $result);
    }

    public function test_json_operations()
    {
        $this->client->shouldReceive('send')->times(2);

        $data = ['test' => 'value'];
        $this->peek->toJson($data);
        $this->peek->json(json_encode($data));
    }

    public function test_html_and_url()
    {
        $this->client->shouldReceive('send')->times(2);

        $this->peek->html('<p>test</p>');
        $this->peek->url('example.com', 'Example');
    }

    public function test_measure()
    {
        $this->client->shouldReceive('send')->times(2);

        $this->peek->measure('test-timer');
        $this->peek->measure(function () {
            return true;
        });
    }

    public function test_exception_handling()
    {
        $this->client->shouldReceive('send')->once();

        $exception = new Exception('Test exception');
        $this->peek->exception($exception);
    }

    public function test_enable_disable()
    {
        $this->assertTrue($this->peek->enabled());

        $this->peek->disable();
        $this->assertFalse($this->peek->enabled());
        $this->assertTrue($this->peek->disabled());

        $this->peek->enable();
        $this->assertTrue($this->peek->enabled());
        $this->assertFalse($this->peek->disabled());
    }

    // public function testCounters()
    // {
    //     $this->client->shouldReceive('send')->times(2);

    //     $this->peek->count('test-counter');
    //     $this->assertEquals(1, $this->peek->counterValue('test-counter'));

    //     $this->peek->clearCounters();
    //     $this->assertEquals(0, $this->peek->counterValue('test-counter'));
    // }
}
