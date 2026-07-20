<?php

namespace Rmphp\Redis;

use Redis;

interface RedisStorageInterface {

	public function set(string $key, mixed $value, mixed $option = null): void;

	public function setEx(string $key, mixed $value): void;

	public function get(string $key): mixed;

	public function exists(mixed $key): bool | int | Redis;

	public function del(array|string $key, string ...$other_keys): void;

	public function getRedis(): Redis;

	public function remember(string $name, callable $function, ?int $expire = null) : mixed;

	public function setTag(string $key, mixed $value, array $tags, mixed $option = null): void;

	public function rememberTag(string $name, array $tags, callable $function, ?int $expire = null) : mixed;

	public function invalidateTag(string ...$tags): void;
}
