<?php

/**
 * KO7_Cache_Memcached class
 * LICENSE: THE WORK (AS DEFINED BELOW) IS PROVIDED UNDER THE TERMS OF THIS
 * CREATIVE COMMONS PUBLIC LICENSE ("CCPL" OR "LICENSE"). THE WORK IS PROTECTED
 * BY COPYRIGHT AND/OR OTHER APPLICABLE LAW. ANY USE OF THE WORK OTHER THAN AS
 * AUTHORIZED UNDER THIS LICENSE OR COPYRIGHT LAW IS PROHIBITED.
 * BY EXERCISING ANY RIGHTS TO THE WORK PROVIDED HERE, YOU ACCEPT AND AGREE TO
 * BE BOUND BY THE TERMS OF THIS LICENSE. TO THE EXTENT THIS LICENSE MAY BE
 * CONSIDERED TO BE A CONTRACT, THE LICENSOR GRANTS YOU THE RIGHTS CONTAINED HERE
 * IN CONSIDERATION OF YOUR ACCEPTANCE OF SUCH TERMS AND CONDITIONS.
 *
 * @link      http://github.com/gimpe/ko7-memcached
 * @author    gimpe <gimpehub@intljaywalkers.com>
 * @copyright 2011 International Jaywalkers
 * @copyright  (c) 2007-2016  Kohana Team
 * @copyright  (c) since 2016 Koseven Team
 * @license   http://creativecommons.org/licenses/by/3.0/ CC BY 3.0
 * @package   KO7/Cache
 */
class KO7_Cache_Memcached extends Cache
{
    protected $memcached_instance;

    protected function __construct(array $config)
    {
        if (! extension_loaded('memcached')) {
            // exception missing memcached extension
            throw new KO7_Cache_Exception('memcached extension is not loaded');
        }

        parent::__construct($config);

        $this->memcached_instance = new Memcached;

        // load servers from configuration
        $servers = Arr::get($this->_config, 'servers', []);

        if (empty($servers)) {
            // exception no server found
            throw new KO7_Cache_Exception('no Memcached servers in config/cache.php');
        }

        // load options from configuration
        $options = Arr::get($this->_config, 'options', []);

        // set options
        foreach ($options as $option => $value) {
            if ($option === Memcached::OPT_SERIALIZER && $value === Memcached::SERIALIZER_IGBINARY
                && ! Memcached::HAVE_IGBINARY) {
                // exception serializer Igbinary not supported
                throw new KO7_Cache_Exception('serializer Igbinary not supported, please fix config/cache.php');
            }

            if ($option === Memcached::OPT_SERIALIZER && $value === Memcached::SERIALIZER_JSON
                && ! Memcached::HAVE_JSON) {
                // exception serializer JSON not supported
                throw new KO7_Cache_Exception('serializer JSON not supported, please fix config/cache.php');
            }

            $this->memcached_instance->setOption($option, $value);
        }

        // add servers
        foreach ($servers as $pos => $server) {
            $host = Arr::get($server, 'host');
            $port = Arr::get($server, 'port', null);
            $weight = Arr::get($server, 'weight', null);
            $status = Arr::get($server, 'status', true);

            if (! empty($host)) {
                // status can be used by an external healthcheck to mark the memcached instance offline
                if ($status === true) {
                    $this->memcached_instance->addServer($host, $port, $weight);
                }
            } else {
                // exception no server host
                throw new KO7_Cache_Exception('no host defined for server[' . $pos . '] in config/cache.php');
            }
        }
    }

    /**
     * Retrieve a cached value entry by id.
     *     // Retrieve cache entry from default group
     *     $data = Cache::instance()->get('foo');
     *     // Retrieve cache entry from default group and return 'bar' if miss
     *     $data = Cache::instance()->get('foo', 'bar');
     *     // Retrieve cache entry from memcache group
     *     $data = Cache::instance('memcache')->get('foo');
     *
     * @param string   id of cache to entry
     * @param string   default value to return if cache miss
     * @return  mixed
     * @throws  KO7_Cache_Exception
     */
    public function get($id, $default = null)
    {
        $result = $this->memcached_instance->get($this->_sanitize_id($id));

        if ($this->memcached_instance->getResultCode() !== Memcached::RES_SUCCESS) {
            $result = $default;
        }

        return $result;
    }

    /**
     * Set a value to cache with id and lifetime
     *     $data = 'bar';
     *     // Set 'bar' to 'foo' in default group, using default expiry
     *     Cache::instance()->set('foo', $data);
     *     // Set 'bar' to 'foo' in default group for 30 seconds
     *     Cache::instance()->set('foo', $data, 30);
     *     // Set 'bar' to 'foo' in memcache group for 10 minutes
     *     if (Cache::instance('memcache')->set('foo', $data, 600))
     *     {
     *          // Cache was set successfully
     *          return
     *     }
     *
     * @param string   id of cache entry
     * @param string   data to set to cache
     * @param integer  lifetime in seconds
     * @return  boolean
     */
    public function set($id, $data, $lifetime = 3600)
    {
        return $this->memcached_instance->set($this->_sanitize_id($id), $data, $lifetime);
    }

    /**
     * Delete a cache entry based on id
     *     // Delete 'foo' entry from the default group
     *     Cache::instance()->delete('foo');
     *     // Delete 'foo' entry from the memcache group
     *     Cache::instance('memcache')->delete('foo')
     *
     * @param string   id to remove from cache
     * @return  boolean
     */
    public function delete($id)
    {
        return $this->memcached_instance->delete($this->_sanitize_id($id));
    }

    /**
     * Delete all cache entries.
     * Beware of using this method when
     * using shared memory cache systems, as it will wipe every
     * entry within the system for all clients.
     *     // Delete all cache entries in the default group
     *     Cache::instance()->delete_all();
     *     // Delete all cache entries in the memcache group
     *     Cache::instance('memcache')->delete_all();
     *
     * @return  boolean
     */
    public function delete_all()
    {
        return $this->memcached_instance->flush();
    }
}
