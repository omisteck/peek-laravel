<?php

namespace Omisteck\Peek\Tests;

use Mockery;
use Omisteck\Peek\Client;
use Omisteck\Peek\Request;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    protected Client $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = new Client(44315, 'localhost');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_server_availability_check()
    {
        $result = $this->client->serverIsAvailable();
        $this->assertIsBool($result);
    }

    public function test_send_request()
    {
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('toJson')->andReturn(json_encode(['test' => 'data']));

        // This might fail if server is not running - that's expected
        $this->client->send($request);
        $this->assertTrue(true); // Assert that no exception was thrown
    }
}
