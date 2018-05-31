<?php
/**
 * @copyright Copyright (c) 2016-2018 Thamtech, LLC
 * @link https://github.com/thamtech/yii2-ratelimiter-advanced
 * @license https://opensource.org/licenses/BSD-3-Clause
**/

namespace tests\unit\allowance;

use Yii;
use tests\unit\TestCase;
use Codeception\Specify;
use thamtech\ratelimiter\allowance\AllowanceCacheStorage;
use yii\caching\DummyCache;

/**
 * AllowanceCacheStorage test
 *
 * @author Tyler Ham <tyler@thamtech.com>
 */
class AllowanceCacheStorageTest extends TestCase
{
    use Specify;

    public function testSaveAllowance()
    {
        $model = new TestableAllowanceCacheStorage([
            'cache' => 'yii\caching\DummyCache',
        ]);

        $this->specify('saveAllowance should work without error', function () use ($model) {
            $model->saveAllowance('id1', null, 123, time(), 3600);
        });

        $model->cache = 'yii\caching\ArrayCache';
        $this->specify('saveAllowance should store the allowance', function () use ($model) {
            $expected = [
                'allowance' => 456,
                'timestamp' => time(),
            ];
            $model->saveAllowance('id2', null, 456, time(), 3600);

            verify('allowance was saved', $model->getCache()->get('allowance-tests\unit\allowance\TestableAllowanceCacheStorage-id2'))->equals($expected);
        });
    }

    public function testLoadAllowance()
    {
        $model = new TestableAllowanceCacheStorage([
            'cache' => 'yii\caching\DummyCache',
        ]);

        $this->specify('loadAllowance should return defaults when cache is empty', function () use ($model) {
            $expected = [
                'allowance' => $model->defaultAllowance,
                'timestamp' => time(),
            ];
            verify('default allowance is returned', $model->loadAllowance('id1', null))->equals($expected);
        });

        $this->specify('loadAllowance should return cached object when found', function() use ($model) {
            $model->cache = Yii::createObject('yii\caching\ArrayCache');
            $expected = [
                'allowance' => 123,
                'timestamp' => time() - 1800, // half an hour ago
            ];
            $model->cache->set('allowance-tests\unit\allowance\TestableAllowanceCacheStorage-id2', $expected, 1800); // half an hour until expiration
            verify('cached allowance is returned', $model->loadAllowance('id2', null))->equals($expected);
        });

        $this->specify('loadAllowance should return defaults when cached object is expired', function () use ($model) {
            $model->cache = Yii::createObject('yii\caching\ArrayCache');
            $cached = [
                'allowance' => 123,
                'timestamp' => time() - 7200, // two hours ago
            ];
            $model->cache->set('allowance-tests\unit\allowance\TestableAllowanceCacheStorage-id3', $cached, -3600); // already expired

            $expected = [
                'allowance' => $model->defaultAllowance,
                'timestamp' => time(),
            ];
            verify('default allowance is returned', $model->loadAllowance('id3', null))->equals($expected);


        });
    }

    public function testGetCacheKey()
    {
        $model = new TestableAllowanceCacheStorage();

        $this->specify('getCacheKey should return correct id', function () use ($model) {
            verify('empty id results in correct key', $model->getCacheKey(''))->equals('allowance-tests\unit\allowance\TestableAllowanceCacheStorage-');
            verify('null id results in correct key', $model->getCacheKey(null))->equals('allowance-tests\unit\allowance\TestableAllowanceCacheStorage-');
            verify('normal id results in correct key', $model->getCacheKey('abc'))->equals('allowance-tests\unit\allowance\TestableAllowanceCacheStorage-abc');

            $model->cacheKeyPrefix = null;
            verify('empty id results in correct key', $model->getCacheKey(''))->equals('-tests\unit\allowance\TestableAllowanceCacheStorage-');
            verify('null id results in correct key', $model->getCacheKey(null))->equals('-tests\unit\allowance\TestableAllowanceCacheStorage-');
            verify('normal id results in correct key', $model->getCacheKey('abc'))->equals('-tests\unit\allowance\TestableAllowanceCacheStorage-abc');
        });
    }

    public function testGetCache()
    {
        $model = new TestableAllowanceCacheStorage();

        $this->specify('getCache should return Cache when properly specified', function () use ($model) {
            $model->cache = 'cache';
            verify('string should load named application cache', $model->getCache())->same(Yii::$app->cache);

            $model->cache = Yii::createObject('yii\caching\DummyCache');
            verify('referenced cache object should be returned', $model->getCache())->same($model->cache);

            $model->cache = 'yii\caching\DummyCache';
            verify('created cache object specified by class name should be returned', $model->getCache())->isInstanceOf(DummyCache::className());

            $model->cache = [
                'class' => 'yii\caching\DummyCache',
            ];
            verify('created cache object specified by array should be returned', $model->getCache())->isInstanceOf(DummyCache::className());
        });
    }

    /**
     * @expectedException yii\base\InvalidConfigException
     * @expectedExceptionMessage The cache component could not be found. It may not be configured properly.
     */
    public function testGetCacheUnrecognizedString()
    {
        $model = new TestableAllowanceCacheStorage();
        $model->cache = 'unrecognizedCache';
        $model->getCache();
    }

    /**
     * @expectedException yii\base\InvalidConfigException
     * @expectedExceptionMessage The cache component could not be found. It may not be configured properly.
     */
    public function testGetCacheUnrecognizedObject()
    {
        $model = new TestableAllowanceCacheStorage();
        $model->cache = Yii::createObject('thamtech\ratelimiter\limit\RateLimit'); // any object not a yii\caching\Cache
        $model->getCache();
    }
}

class TestableAllowanceCacheStorage extends AllowanceCacheStorage
{
    public function getCache()
    {
        return parent::getCache();
    }

    public function getCacheKey($id)
    {
        return parent::getCacheKey($id);
    }
}
