<?php

declare(strict_types=1);

namespace Onion\Framework\Redis\Extensions\Acl;

use Onion\Framework\Redis\Client;
use Onion\Framework\Promise\Interfaces\PromiseInterface;

class Acl
{
    public function __construct(
        private readonly Client $client,
    ) {
    }

    public function cat(string $category = null): PromiseInterface
    {
        return $this->client->raw('ACL', 'CAT', $category);
    }

    public function delete(string $user, string ...$users): PromiseInterface
    {
        return $this->client->raw('ACL', 'DELUSER', $user, ...$users);
    }

    public function dry(string $user, array $command): PromiseInterface
    {
        return $this->client->raw('ACL', 'DRYRUN', $user, ...$command);
    }

    public function password(int $size = null): PromiseInterface
    {
        return $this->client->raw('ACL', 'GENPASS', $size);
    }

    public function get(string $user): PromiseInterface
    {
        return $this->client->raw('ACL', 'GETUSER', $user);
    }

    public function list(): PromiseInterface
    {
        return $this->client->raw('ACL', 'LIST');
    }

    public function load(): PromiseInterface
    {
        return $this->client->raw('ACL', 'LOAD');
    }

    public function save(): PromiseInterface
    {
        return $this->client->raw('ACL', 'SAVE');
    }

    public function add(string $user, mixed ...$rules)
    {
        return $this->client->raw('ACL', 'SETUSER', $user, ...$rules);
    }

    public function users(): PromiseInterface
    {
        return $this->client->raw('ACL', 'USERS');
    }

    public function whoami(): PromiseInterface
    {
        return $this->client->raw('ACL', 'WHOAMI');
    }
}
