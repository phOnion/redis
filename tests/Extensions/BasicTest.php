<?php

namespace Tests\Extensions;

use Onion\Framework\Redis\Client;
use Onion\Framework\Redis\Extensions\Basic\Basic;
use Onion\Framework\Redis\Extensions\Basic\Extension;
use Onion\Framework\Test\TestCase;

class BasicTest extends TestCase
{
    private Client $client;

    public function setUp(): void
    {
        $this->client = new Client('tcp://127.0.0.1');
        $this->client->register(new Extension());
    }

    public function tearDown(): void
    {
        $this->client->quit();
    }


    public function testGet()
    {
        /** @var Basic $basic */
        $basic = $this->client->basic();

        $basic->keys()->then($this->assertEmpty(...));
    }
}
