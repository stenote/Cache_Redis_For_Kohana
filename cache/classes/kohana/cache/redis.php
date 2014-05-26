<?php defined('SYSPATH') or die('No direct script access.');

class Kohana_Cache_Redis extends Cache implements Cache_Arithmetic {

    /**
     * redis resource
     *
     * @var redis
     */
    protected $_redis;

    /**
     * Constructs the redis Kohana_Cache object
     *
     * @param   array     configuration
     * @throws  Cache_Exception
     */
    protected function __construct(array $config)
    {
        // Check for the redis extention
        if ( ! extension_loaded('redis'))
        {
            throw new Cache_Exception('redis PHP extention not loaded');
        }

        parent::__construct($config);

        // Setup redis
        $this->_redis = new redis;

        $server = Arr::get($this->_config, 'server', NULL);

        if ( ! $server)
        {
            // Throw an exception if no server found
            throw new Cache_Exception('No redis servers defined in configuration');
        }

        if ($server['persistent']) {
            if (!$this->_redis->pconnect($server['host'], $server['port'], $server['timeout'])) {
                throw new Cache_Exception('redis could not connect to host \':host\' using port \':port\'', array(':host' => $server['host'], ':port' => $server['port']));
            }
        }
        else {
            if (!$this->_redis->connect($server['host'], $server['port'], $server['timeout'])) {
                throw new Cache_Exception('redis could not connect to host \':host\' using port \':port\'', array(':host' => $server['host'], ':port' => $server['port']));
            }
        }
    }

    /**
     * Retrieve a cached value entry by id.
     *
     *     // Retrieve cache entry from redis group
     *     $data = Cache::instance('redis')->get('foo');
     *
     *     // Retrieve cache entry from redis group and return 'bar' if miss
     *     $data = Cache::instance('redis')->get('foo', 'bar');
     *
     * @param   string   id of cache to entry
     * @param   string   default value to return if cache miss
     * @return  mixed
     * @throws  Cache_Exception
     */
    public function get($id, $default = NULL)
    {
        // Get the value from redis
        $value = $this->_redis->get($this->_sanitize_id($id));

        // If the value wasn't found, normalise it
        if ($value === FALSE)
        {
            $value = (NULL === $default) ? NULL : $default;
        }

        // Return the value
        return $value;
    }

    /**
     * Set a value to cache with id and lifetime
     *
     *     $data = 'bar';
     *
     *     // Set 'bar' to 'foo' in redis group for 10 minutes
     *     if (Cache::instance('redis')->set('foo', $data, 600))
     *     {
     *          // Cache was set successfully
     *          return
     *     }
     *
     * @param   string   id of cache entry
     * @param   mixed    data to set to cache
     * @param   integer  lifetime in seconds, maximum value 2592000
     * @return  boolean
     */
    public function set($id, $data, $lifetime = 3600)
    {

        // Set the data to redis
        return $this->_redis->setex($this->_sanitize_id($id), $lifetime, $data);
    }

    public function ttl($id)
    {
        return $this->_redis->ttl($this->_sanitize_id($id));
    }

    /**
     * Delete a cache entry based on id
     *
     *     // Delete the 'foo' cache entry immediately
     *     Cache::instance('redis')->delete('foo');
     *
     *     // Delete the 'bar' cache entry after 30 seconds
     *     Cache::instance('redis')->delete('bar', 30);
     *
     * @param   string   id of entry to delete
     * @param   integer  timeout of entry, if zero item is deleted immediately, otherwise the item will delete after the specified value in seconds
     * @return  boolean
     */
    public function delete($id)
    {
        return $this->_redis->delete($this->_sanitize_id($id));
    }

    /**
     * Delete all cache entries.
     *
     * Beware of using this method when
     * using shared memory cache systems, as it will wipe every
     * entry within the system for all clients.
     *
     *     // Delete all cache entries in the default group
     *     Cache::instance('redis')->delete_all();
     *
     * @return  boolean
     */
    public function delete_all()
    {
        $result = $this->_redis->flushall();

        // We must sleep after flushing, or overwriting will not work!
        // @see http://php.net/manual/en/function.redis-flush.php#81420
        sleep(1);

        return $result;
    }

    /**
     * Increments a given value by the step value supplied.
     * Useful for shared counters and other persistent integer based
     * tracking.
     *
     * @param   string    id of cache entry to increment
     * @param   int       step value to increment by
     * @return  integer
     * @return  boolean
     */
    public function increment($id, $step = 1)
    {
        return $this->_redis->incr($id, $step);
    }

    /**
     * Decrements a given value by the step value supplied.
     * Useful for shared counters and other persistent integer based
     * tracking.
     *
     * @param   string    id of cache entry to decrement
     * @param   int       step value to decrement by
     * @return  integer
     * @return  boolean
     */
    public function decrement($id, $step = 1)
    {
        return $this->_redis->decr($id, $step);
    }

    /**
     * 判断redis中是否存在某个key
     *
     * @param   string key
     * @return  boolean 返回是否存在结果
     */
    public function exists($key)
    {
        return $this->_redis->exists($key);
    }

    /**
     * 设定redis某个key过期时间
     *
     * @param   string key time
     * @return  boolean 返回设定过期时间是否成功
     */
    public function expire($key, $time)
    {
        return $this->_redis->expire($key, $time);
    }
}
