<?php

declare(strict_types=1);

namespace Onion\Framework\Redis;

use Closure;
use RuntimeException;

class Serializer
{
    public function __construct(
        private ?Closure $serialize = null,
        private ?Closure $unserialize = null,
    ) {
        $this->serialize ??= serialize(...);
        $this->unserialize ??= unserialize(...);
    }

    public function serialize(mixed $value): string
    {
        return match (strtolower(gettype($value))) {
            'string' => '"' . addslashes($value) . '"',
            'float' => "\"{$value}\"",
            'double' => "\"{$value}\"",
            'null' => '',
            'bool' => $value ? 'true' : 'false',
            'boolean' => $value ? 'true' : 'false',
            'int' => "{$value}",
            'integer' => "{$value}",
            default => '"' . addslashes(($this->serialize)($value)) . '"',
        };
    }

    public function unserialize(
        string $buffer,
        Client $client,
    ): mixed {
        $result = match ($buffer[0]) {
            '+' => match (substr($buffer, 1)) {
                'OK' => true,
                'QUEUED' => true,
                default => substr($buffer, 1),
            },
            '(' => substr($buffer, 1),
            '-' => throw new RuntimeException($buffer),
            '$' => $this->retrieveString(substr($buffer, 1), $client),
            '=' => $this->retrieveString(substr($buffer, 5), $client),
            ':' => (int) substr($buffer, 1),
            ';' => $client->getConnection()->read((int) substr($buffer, 1) + 2),
            '*' => $this->retrieveArray((int) substr($buffer, 1), $client),
            '~' => $this->retrieveArray((int) substr($buffer, 1), $client),
            '%' => $this->retrieveMap((int) substr($buffer, 1), $client),
            '|' => [$this->retrieveMap((int) substr($buffer, 1), $client), $client->receive()],
            '_' => null,
            ',' => $this->retrieveFloat(substr($buffer, 1)),
            '#' => match ($buffer) {
                '#t' => true,
                '#f' => false,
            },
            '!' => throw new RuntimeException($client->getConnection()->read((int) substr($buffer, 1))),
            '>' => $client->notify($this->retrieveArray((int) substr($buffer, 1), $client)),
            default => $buffer,
        };

        if (is_string($result)) {
            $decoded = @($this->unserialize)($result);

            $result = $decoded ?: match (true) {
                ctype_digit($result) => (int) $result,
                'true' === $result => true,
                'false' === $result => false,
                '' === $result => null,
                $result === (string) (float) $result => (float) $result,
                default => $result,
            };
        }

        return $result;
    }

    protected function retrieveString(string | int $size, Client $client): string
    {
        $buffer = '';

        if ($size === '?') {
            do {
                $buffer .= ($chunk = $client->receive());
            } while ($chunk !== '');
        } else {
            $buffer = $client->getConnection()->read(((int) $size) + 2);
        }

        return stripslashes(rtrim($buffer));
    }

    protected function retrieveArray(int $size, Client $client): array
    {
        $arr = [];
        if ($size === '?') {
            $counter = 0;
            $chunk = '';
            do {
                $chunk = $client->receive();
                if ($chunk === '.') {
                    break;
                }

                $arr[$counter] = $chunk;
            } while (true);
        } else {
            for ($i = 0; $i < $size; $i++) {
                $arr[$i] = $client->receive();
            }
        }

        return $arr;
    }

    protected function retrieveMap(int $size, Client $client): array
    {
        $arr = [];
        for ($i = 0; $i < $size; $i++) {
            $arr[$client->receive()] = $client->receive();
        }

        return $arr;
    }

    private function retrieveFloat(string $number): array
    {
        return match (strtoupper($number)) {
            'inf' => INF,
            '-inf' => - INF,
            default => (float) $number,
        };
    }
}
