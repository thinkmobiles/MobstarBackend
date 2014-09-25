<?php

/**
 * This file is part of Laravel Flysystem by Graham Campbell.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at http://bit.ly/UWsjkb.
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace GrahamCampbell\Flysystem\Cache;

use GrahamCampbell\Flysystem\FlysystemManager;
use Illuminate\Cache\CacheManager;

/**
 * This is the cache connection factory class.
 *
 * @author    Graham Campbell <graham@mineuk.com>
 * @copyright 2014 Graham Campbell
 * @license   <https://github.com/GrahamCampbell/Laravel-Flysystem/blob/master/LICENSE.md> Apache 2.0
 */
class ConnectionFactory
{
    /**
     * The cache manager instance.
     *
     * @var \Illuminate\Cache\CacheManager
     */
    protected $cache;

    /**
     * Create a new connection factory instance.
     *
     * @param \Illuminate\Cache\CacheManager $cache
     *
     * @return void
     */
    public function __construct(CacheManager $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Establish a cache connection.
     *
     * @param array                                      $config
     * @param \GrahamCampbell\Flysystem\FlysystemManager $manager
     *
     * @return \League\Flysystem\CacheInterface
     */
    public function make(array $config, FlysystemManager $manager)
    {
        return $this->createConnector($config, $manager)->connect($config);
    }

    /**
     * Create a connector instance based on the configuration.
     *
     * @param array                                      $config
     * @param \GrahamCampbell\Flysystem\FlysystemManager $manager
     *
     * @throws \InvalidArgumentException
     *
     * @return \GrahamCampbell\Manager\ConnectorInterface
     */
    public function createConnector(array $config, FlysystemManager $manager)
    {
        if (!isset($config['driver'])) {
            throw new \InvalidArgumentException("A driver must be specified.");
        }

        switch ($config['driver']) {
            case 'illuminate':
                return new IlluminateConnector($this->cache);
            case 'adapter':
                return new AdapterConnector($manager);
        }

        throw new \InvalidArgumentException("Unsupported driver [{$config['driver']}]");
    }

    /**
     * Get the cache manager instance.
     *
     * @codeCoverageIgnore
     *
     * @return \Illuminate\Cache\CacheManager
     */
    public function getCache()
    {
        return $this->cache;
    }
}
