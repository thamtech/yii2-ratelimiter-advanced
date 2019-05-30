<?php
/**
 * @copyright Copyright (c) 2016-2018 Thamtech, LLC
 * @link https://github.com/thamtech/yii2-ratelimiter-advanced
 * @license https://opensource.org/licenses/BSD-3-Clause
 **/

namespace thamtech\ratelimiter\allowance;

use Yii;
use yii\base\InvalidConfigException;
use yii\base\BaseObject;
use yii\caching\Cache;

/**
 * AllowanceCacheStorage allows you to store allowance information in
 * a cache component.
 *
 * @author Tyler Ham <tyler@thamtech.com>
 */
class AllowanceCacheStorage extends BaseObject implements AllowanceStorageInterface
{
    /**
     * @var Cache|string|array the cache object, the ID of the cache
     *     application component, or a Cache definition array, describing the
     *     cache component that should be used for storage.
     */
    public $cache;

    /**
     * @var string cache key prefix that will be combined with this
     *     class name and the rate limit identifier when storing and
     *     retrieving cache items.
     */
    public $cacheKeyPrefix = 'allowance';

    /**
     * @var int the default allowance value to return when the
     *     specified cache key does not exist yet.
     *
     *     A large value here will automatically be trimmed to the
     *     window's rate limit in [[thamtech\ratelimiter\RateLimiter]]'s
     *     implementation of the leaky bucket algorithm. This is essentially
     *     a full bucket.
     */
    public $defaultAllowance = PHP_INT_MAX;

    /**
     * @inheritdoc
     */
    public function loadAllowance($id, $context)
    {
        $cache = $this->getCache();
        $key = $this->getCacheKey($id);

        $value = $cache->get($key);

        if ($value === false) {
            // return default
            return [
                'allowance' => $this->defaultAllowance,
                'timestamp' => time(),
            ];
        }

        return $value;
    }

    /**
     * @inheritdoc
     */
    public function saveAllowance($id, $context, $allowance, $timestamp, $window)
    {
        $cache = $this->getCache();
        $key = $this->getCacheKey($id);

        $cache->set($key, [
            'allowance' => $allowance,
            'timestamp' => time(),
        ], $window);
    }

    /**
     * Get the [[Cache]] component.
     *
     * @return Cache the Cache component
     *
     * @throws InvalidConfigException if the cache component is not found
     */
    protected function getCache()
    {
        if ($this->cache instanceof Cache) {
            return $this->cache;
        }

        if (is_string($this->cache) && isset(Yii::$app->{$this->cache})) {
            $cache = Yii::$app->get($this->cache, false);
        } else {
            try {
                $cache = Yii::createObject($this->cache);
            } catch (\Exception $e) {
                $cache = false;
            }
        }

        if ($cache instanceof Cache) {
            $this->cache = $cache;

            return $this->cache;
        }

        throw new InvalidConfigException('The cache component could not be found. It may not be configured properly.');
    }

    /**
     * Gets the cache key by joining the [[cacheKeyPrefix]],
     * this class name, and the given $id.
     *
     * @param string allowance scope ID
     *
     * @return mixed the cache key
     */
    protected function getCacheKey($id)
    {
        return [
            static::class,
            $this->cacheKeyPrefix,
            $id,
        ];
    }
}
