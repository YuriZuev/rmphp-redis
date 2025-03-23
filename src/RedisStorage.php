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
	public function __construct(array $params) {
		if(isset($params['database'])){
			if(is_int($params['database'])) $database = $params['database'];
			unset($params['database']);
			if(is_int($params['defaultExpire'])) $this->expire = $params['defaultExpire'];
			unset($params['defaultExpire']);
		}
		$this->redis = new Redis($params);
		if(!empty($database)) $this->redis->select($database);
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
	 * @return Redis
	 */
	public function getRedis(): Redis {
		return $this->redis;
	}


}
