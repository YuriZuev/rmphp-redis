<?php

namespace Rmphp\Redis;

use Redis;

interface RedisStorageInterface {

	public function set(string $key, mixed $value, mixed $option = null): void;

	public function setEx(string $key, mixed $value): void;

	public function get(string $key): mixed;

	public function exists(mixed $key): bool | int | Redis;

	public function getRedis(): Redis;
}
