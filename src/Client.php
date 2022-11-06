<?php

namespace Onion\Framework\Redis;

use BadMethodCallException;
use Closure;
use InvalidArgumentException;
use Onion\Framework\Client\Client as ClientClient;
use Onion\Framework\Client\Interfaces\ContextInterface;
use Onion\Framework\Loop\Interfaces\ResourceInterface;
use Onion\Framework\Loop\Types\Operation;
use Onion\Framework\Promise\Deferred;
use Onion\Framework\Promise\Interfaces\DeferredInterface;
use Onion\Framework\Promise\Interfaces\PromiseInterface;
use Onion\Framework\Promise\Promise;
use Onion\Framework\Redis\Interfaces\ExtensionInterface;
use RuntimeException;
use SplQueue;
use Throwable;

use function Onion\Framework\Loop\coroutine;

class Client
{
    private readonly string $clusterKey;
    private readonly ResourceInterface $connection;

    private bool $batched = false;
    private SplQueue $buffer;

    private array $extensions = [];

    public function __construct(
        private readonly string $dsn,
        private ?Serializer $serializer = null,
        array $contexts = [],
    ) {
        $this->buffer = new SplQueue();
        $this->buffer->setIteratorMode(SplQueue::IT_MODE_FIFO);
        $this->serializer ??= new Serializer();
        $connection = parse_url($this->dsn);

        $connection['port'] ??= 6379;
        $connection['host'] ??= $connection['path'];
        $connection['scheme'] ??= 'tcp';
        $connection['pass'] ??= null;

        $this->connection = ClientClient::connect(
            "{$connection['scheme']}://{$connection['host']}:{$connection['port']}",
            null,
            ...array_filter($contexts, fn ($ctx) => $ctx instanceof ContextInterface),
        );

        $this->clusterKey = "{$connection['host']} {$connection['port']}";
    }

    public function getClusterKey(): string
    {
        return $this->clusterKey;
    }

    public function getConnection(): ?ResourceInterface
    {
        return $this->connection;
    }

    public function register(ExtensionInterface $extension): void
    {
        $this->extensions[$extension->getName()] = $extension;
    }

    public function extension(string $name): ExtensionInterface
    {
        if (!isset($this->extensions[$name])) {
            throw new InvalidArgumentException(
                "Requested extension '{$name}' is not registered, registered are " .
                    implode(', ', $this->getRegisteredExtensions())
            );
        }

        return $this->extensions[$name];
    }

    public function getRegisteredExtensions(): array
    {
        return array_keys($this->extensions);
    }

    public function __call(string $name, array $arguments)
    {
        $name = strtolower($name);
        if (!isset($this->extensions[$name])) {
            throw new BadMethodCallException("Method '{$name}' does not exist");
        }

        return $this->extension($name)
            ->create(
                $this,
                $this->serializer,
                $this->receive(...),
                ...$arguments
            );
    }

    private function poll()
    {
        $this->connection->wait(Operation::WRITE);

        [$command, $deferred] = $this->buffer->dequeue();

        $cmd = implode(' ', array_map($this->serializer->serialize(...), $command));
        $this->connection->write("{$cmd}\r\n");

        $this->connection->wait(Operation::READ);

        /** @var DeferredInterface $deferred */
        try {
            $deferred->resolve($this->receive());

            // if (is_pending($this->connection)) {
            //     $this->receive();
            // }
        } catch (Throwable $ex) {
            if (substr($ex->getMessage(), 0, 3) === 'ASK') {
                $redirect = substr($ex->getMessage(), 1, 3);
                $deferred->resolve($this->cluster->send($redirect, ['ASKING'])
                    ->then(fn () => $this->cluster->send($redirect, $command)));
            } elseif (substr($ex->getMessage(), 1, 5) === 'MOVED') {
                [, $redirect] = explode(' ', substr($ex->getMessage(), 7), 2);

                $deferred->resolve($this->cluster->send(str_replace(':', ' ', $redirect), $command));
            } else {
                $deferred->reject(new RuntimeException("Error while executing '{$cmd}'", previous: $ex));
            }
        }
    }

    private function send(array $command): PromiseInterface
    {
        $deferred = new Deferred();
        $this->buffer[] = [$command, $deferred];
        coroutine($this->poll(...));

        return $deferred->promise();
    }

    public function receive(): mixed
    {
        $buffer = '';
        while (($chunk = $this->connection->read(1)) !== "\n") {
            $buffer .= $chunk;
        }

        $buffer = trim($buffer);
        if ($buffer === '') {
            return null;
        }

        return $this->serializer->unserialize($buffer, $this);
    }

    // todo: worth having?
    public function notify(mixed $data): void
    {
        var_dump($data);
    }

    public function raw(mixed ...$command): PromiseInterface
    {
        if ($this->batched) {
            $this->queue[] = $command;
            return new Promise(fn () => null);
        }

        return $this->send($command);
    }

    public function auth(string $password, string $username = null): PromiseInterface
    {
        return $this->raw('AUTH', $username, $password);
    }

    public function hello(int $version = 2, array $credentials = null, string $name = null): PromiseInterface
    {
        if (isset($credentials)) {
            array_unshift($credentials, 'AUTH');
        }

        if (isset($name)) {
            $name = [
                'SETNAME',
                $name,
            ];
        }

        return $this->raw(
            'HELLO',
            $version,
            ...($credentials ?? []),
            ...($name ?? [])
        );
    }

    public function reset(): PromiseInterface
    {
        return $this->raw('RESET');
    }

    public function quit(): PromiseInterface
    {
        return $this->raw('QUIT')
            ->then($this->connection->close(...));
    }

    public function ping(?string $message = null): PromiseInterface
    {
        return $this->raw('PING', $message)
            ->then(
                fn ($reply) => isset($message) ? $message === $reply : $reply === 'PONG'
            );
    }

    public function select(int $index): PromiseInterface
    {
        return $this->raw('SELECT', $index);
    }

    public function batch(Closure $fn): PromiseInterface
    {
        $this->batched = true;
        ($fn)($this->client);
        $this->batched = false;

        $queue = $this->buffer;
        $this->buffer = new SplQueue();

        return $this->client
            ->raw(implode("\r\n", iterator_to_array($queue)))
            ->then(
                function ($value) use ($queue) {
                    $results = [$value];

                    for ($i = 0; $i < count($queue); $i++) {
                        $results[] = $this->receive();
                    }

                    return $results;
                }
            );
    }
}
