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
			parse_str(strtolower($parsedDSN['query']), $options);
		}
		$options['host'] = $parsedDSN['host'];
		$options['port'] = $parsedDSN['port'] ?? 6379;

		if (isset($parsedDSN['user'])) {
			$options['username'] = $parsedDSN['user'];
		}

		if (isset($parsedDSN['pass'])) {
			$options['password'] = $parsedDSN['pass'];
		}

		if(isset($options['defaultexpire'])) {
			$this->expire = (int) $options['defaultexpire'];
			unset($options['defaultexpire']);
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
		if($this->redis->exists($name)){
			return $this->redis->get($name);
		}
		$value = $function();
		$this->redis->set($name, $value, $expire ?? $this->expire);
		return $value;
	}

	/**
	 * @throws RedisException
	 */
	public function setTag(string $key, mixed $value, array $tags, mixed $option = null): void {
		$this->redis->set($key, $value, $option);
		foreach ($tags as $tag) {
			$this->redis->sAdd($this->tagKey($tag), $key);
		}
	}

	/**
	 * @throws RedisException
	 */
	public function rememberTag(string $name, array $tags, callable $function, ?int $expire = null) : mixed {
		if($this->redis->exists($name)){
			return $this->redis->get($name);
		}
		$value = $function();
		$this->setTag($name, $value, $tags, $expire ?? $this->expire);
		return $value;
	}

	/**
	 * @throws RedisException
	 */
	public function invalidateTag(string ...$tags): void {
		foreach ($tags as $tag) {
			$tagKey = $this->tagKey($tag);
			$keys = $this->redis->sMembers($tagKey);
			if ($keys) {
				$this->redis->del($keys);
			}
			$this->redis->del($tagKey);
		}
	}

	private function tagKey(string $tag): string {
		return "tag:$tag";
	}
}
