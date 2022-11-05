<?php

declare(strict_types=1);

namespace Onion\Framework\Redis\Extensions\PubSub;

use Onion\Framework\Promise\Interfaces\PromiseInterface;
use Onion\Framework\Redis\Client;

use function Onion\Framework\Loop\{channel, coroutine, read};

class PubSub
{
    /** @var ChannelInterface[] */
    private array $subscriptions = [];

    public function __construct(
        private readonly Client $client
    ) {
    }

    public function subscribe(string $channel, string ...$channels): PromiseInterface
    {
        return $this->client->raw(
            'PUSBSCRIBE',
            $channel,
            ...$channels,
        )->then(function () use ($channel, $channels) {
            $this->subscriptions[$channel] ??= channel();
            foreach ($channels as $ch) {
                $this->subscriptions[$ch] ??= channel();
            }
        })->then($this->message(...));
    }

    public function unsubscribe(string $channel, string ...$channels): PromiseInterface
    {
        return $this->client->raw(
            'UNSUBSCRIBE',
            $channel,
            ...$channels,
        )->then(function () use ($channel, $channels) {
            $this->subscriptions[$channel]?->close();
            unset($this->subscribers[$channel]);

            foreach ($channels as $ch) {
                $this->subscriptions[$ch]?->close();
                unset($this->subscribers[$ch]);
            }
        });
    }

    public function publish(string $channel, mixed $message): PromiseInterface
    {
        return $this->client->raw('PUBLISH', $channel, $message);
    }

    private function notify(string $channel, mixed $data): void
    {
        $this->subscriptions[$channel]?->send($data);
    }

    private function message(): void
    {
        if (empty($this->subscriptions)) {
            return;
        }

        coroutine(read(...), [$this->client->getConnection(), function () {
            if (empty($this->subscriptions)) {
                return;
            }

            $reply = ($this->receive)();

            switch (strtolower($reply[0])) {
                case 'message':
                    [, $channel, $data] = $reply;
                    $this->notify($channel, $data);
                    break;
                case 'pmessage':
                    [, $pattern, $channel, $data] = $reply;
                    $this->notify($pattern, $data);
                    break;
            }

            $this->message();
        }]);
    }
}
