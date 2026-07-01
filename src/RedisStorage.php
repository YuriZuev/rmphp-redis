<?php

namespace Rmphp\Redis;

use Redis;
use RedisException;

class RedisStorage implements RedisStorageInterface {

	private Redis $redis;
	private int $expire = 300;

	/**
	 * @throws RedisException
	 */
	public function __construct(string $dsn) {
		$parsedDSN = parse_url($dsn);

		if (isset($parsedDSN['query'])) {
			parse_str($parsedDSN['query'], $options);
		}
		$options['host'] = $parsedDSN['host'] ?? 'localhost';
		$options['port'] = $parsedDSN['port'] ?? 6379;

		if (isset($parsedDSN['user']) && isset($parsedDSN['pass'])) {
			$options['auth'] = [$parsedDSN['user'], $parsedDSN['pass']];
		} elseif (isset($parsedDSN['pass'])) {
			$options['auth'] = $parsedDSN['pass'];
		}

		if(isset($options['defaultExpire'])) {
			$this->expire = (int) $options['defaultExpire'];
			unset($options['defaultExpire']);
		}

		$this->redis = new Redis($options);
		$this->redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP);

		$database = (int) ltrim($parsedDSN['path'] ?? '', '/');
		$this->redis->select($database);
	}

	/**
	 * @throws RedisException
	 */
	public function set(string $key, mixed $value, mixed $option = null): void {
		$this->redis->set($key, $value, $option);
	}

	/**
	 * @throws RedisException
	 */
	public function setEx(string $key, mixed $value): void {
		$this->redis->set($key, $value, $this->expire);
	}

	/**
	 * @throws RedisException
	 */
	public function get(string $key) : mixed {
		return $this->redis->get($key);
	}

	/**
	 * @throws RedisException
	 */
	public function exists(mixed $key) : bool|int|Redis {
		return $this->redis->exists($key);
	}

	/**
	 * @param array|string $key
	 * @param string ...$other_keys
	 * @return void
	 */
	public function del(array|string $key, string ...$other_keys): void {
		$this->redis->del($key, ...$other_keys);
	}

	/**
	 * @return Redis
	 */
	public function getRedis(): Redis {
		return $this->redis;
	}

	/**
	 * @param string $name
	 * @param callable $function
	 * @param int|null $expire
	 * @return mixed
	 */
	public function remember(string $name, callable $function, ?int $expire = null) : mixed {
		$value = $this->redis->get($name);
		if ($value !== false || $this->redis->exists($name)) {
			return $value;
		}
		$value = $function();
		$this->redis->set($name, $value, $expire ?? $this->expire);
		return $value;
	}
}
