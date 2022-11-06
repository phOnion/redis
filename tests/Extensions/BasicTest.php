<?php

namespace Tests\Extensions;

use Onion\Framework\Redis\Client;
use Onion\Framework\Redis\Extensions\Basic\Basic;
use Onion\Framework\Redis\Extensions\Basic\Extension;
use Onion\Framework\Test\TestCase;
use stdClass;

use function Onion\Framework\Promise\await;

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
        $this->client->basic()->flush();
        $this->client->quit();
    }

    public function readWriteDataProvider(): array
    {
        return [
            ['name' => 'foo', 'value' => 'bar'],
            ['name' => 'baz', 'value' => 123],
            ['name' => 'test', 'value' => [true]],
            ['name' => 'variable', 'value' => ['f' => ['v' => ['o' => true]]]],
            ['name' => 'bool', 'value' => true],
            ['name' => 'floaty', 'value' => 1.2345],
        ];
    }

    /**
     * @dataProvider readWriteDataProvider
     */
    public function testBasicReadWrite(string $name, mixed $value): void
    {
        /** @var Basic $basic */
        $basic = $this->client->basic();
        $basic->set($name, $value)
            ->then(fn () => $basic->get($name))
            ->then(fn ($v) => $this->assertSame($value, $v));
    }

    public function getDataProvider(): array
    {
        return [
            ['pattern' => '*', 'values' => [], 'expectation' => []],
            ['pattern' => 'foo*', 'values' => ['foo' => true, 'bar' => true, 'foot' => false], 'expectation' => ['foo', 'foot']],
            ['pattern' => 'foo', 'values' => [
                'foo' => true,
                'bar' => true,
                'foot' => false,
            ], 'expectation' => ['foo']],
        ];
    }

    /**
     * @dataProvider getDataProvider
     */
    public function testGet(string $pattern, array $values, array $expectation)
    {
        /** @var Basic $basic */
        $basic = $this->client->basic();

        $basic->keys($pattern)->then(fn ($response) => $this->assertSame($expectation, $response));
    }
}
