<?php

declare(strict_types=1);

namespace Onion\Framework\Redis\Extensions\Monitor;

use Closure;
use Onion\Framework\Redis\Client;
use Onion\Framework\Loop\Channels\Channel;
use Onion\Framework\Promise\Interfaces\PromiseInterface;
use Onion\Framework\Promise\Promise;

use function Onion\Framework\Loop\coroutine;
use function Onion\Framework\Loop\is_pending;

class Monitor
{
    private Channel $channel;
    private bool $state = false;

    public function __construct(
        private readonly Client $client,
    ) {
    }

    public function started(): bool
    {
        return $this->state;
    }

    private function poll(Closure $notify)
    {
        coroutine(function () use ($notify) {
            while (is_pending($this->client->getConnection())) {
                ($notify)($this->client->receive());
            }

            if ($this->state) {
                $this->poll($notify);
            }
        });
    }

    public function start(Closure $notify): PromiseInterface
    {
        if ($this->state) {
            return Promise::resolve($this->channel);
        }

        return $this->client->raw('MONITOR')
            ->then(function ($result) use ($notify) {
                $this->state = true;
                $this->poll($notify);

                return $result;
            });
    }

    public function stop(): void
    {
        if (!$this->state) {
            return;
        }

        $this->state = false;
        $this->client->reset();
    }
}
