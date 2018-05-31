<?php
/**
 * @copyright Copyright (c) 2016-2018 Thamtech, LLC
 * @link https://github.com/thamtech/yii2-ratelimiter-advanced
 * @license https://opensource.org/licenses/BSD-3-Clause
**/

namespace tests\unit\limit;

use tests\unit\TestCase;
use Codeception\Specify;
use thamtech\ratelimiter\limit\RateLimit;


/**
 * RateLimit test
 *
 * @author Tyler Ham <tyler@thamtech.com>
 */
class RateLimitTest extends TestCase
{

    use Specify;

    public function testGetRateLimit()
    {
        $model = new RateLimit();

        $this->specify('getRateLimit should return self', function () use ($model) {
            verify('should return self', $model->getRateLimit(null))->same($model);
        });
    }

    public function testIsActive()
    {
        $model = new RateLimit();

        $this->specify('scalar should return true or false', function () use ($model) {
            verify('default true should be active', $model->isActive(null, null))->true();

            $model->active = false;
            verify('false should be inactive', $model->isActive(null, null))->false();

            $model->active = 2;
            verify('2 should be active', $model->isActive(null, null))->true();

            $model->active = 0;
            verify('0 should be inactive', $model->isActive(null, null))->false();

            $model->active = 'abc';
            verify('abc should be active', $model->isActive(null, null))->true();

            $model->active = '';
            verify('empty string should be inactive', $model->isActive(null, null))->false();
        });

        $this->specify('closure should return proper result', function () use ($model) {
            $model->active = function () {
                return true;
            };
            verify('should be active', $model->isActive(null, null))->true();

            $model->active = function () {
                return false;
            };
            verify('should be inactive', $model->isActive(null, null))->false();

            $model->active = function ($a) {
                return $a ? true : false;
            };
            verify('with true param should be active', $model->isActive(true, null))->true();
            verify('with false param should be inactive', $model->isActive(false, null))->false();

            $model->active = function ($a, $b) {
                return $a ? true : ($b ? true : false);
            };
            verify('with true,true param should be active', $model->isActive(true, true))->true();
            verify('with true,false param should be active', $model->isActive(true, false))->true();
            verify('with false,true param should be active', $model->isActive(false, true))->true();
            verify('with false,false param should be active', $model->isActive(false, false))->false();
        });

        $this->specify('callable should return proper result', function () use ($model) {
            $model->active = [$this, 'callableA'];
            verify('should be active', $model->isActive(null, null))->true();

            $model->active = [$this, 'callableB'];
            verify('should be active', $model->isActive(null, '123'))->true();

            $model->active = [$this, 'callableC'];
            verify('should be inactive', $model->isActive(null, null))->false();
        });
    }

    public function testGetId()
    {
        $model = new RateLimit();

        $this->specify('scalar id should return null or string', function () use ($model) {
            verify('id should be null', $model->getId(null, null))->null();

            $model->identifier = 'abc';
            verify('id should return a string', $model->getId(null, null))->same('abc');

            $model->identifier = 123;
            verify('id should return a string', $model->getId(null, null))->same('123');

            $model->identifier = 1.23;
            verify('id should return a string', $model->getId(null, null))->same('1.23');
        });

        $this->specify('closure id should return null or string', function () use ($model) {
            $model->identifier = function($a) {
                return 'abc';
            };
            verify('id should return a string', $model->getId(null, null))->same('abc');

            $model->identifier = function($a, $b) {
                return 'abc-' . $b;
            };
            verify('id should return a string', $model->getId(null, '123'))->same('abc-123');

            $model->identifier = function() {
                return null;
            };
            verify('id should be null', $model->getId(null, null))->null();
        });

        $this->specify('callable id should return null or string', function () use ($model) {
            $model->identifier = [$this, 'callableA'];
            verify('id should return a string', $model->getId(null, null))->same('abc');

            $model->identifier = [$this, 'callableB'];
            verify('id should return a string', $model->getId(null, '123'))->same('abc-123');

            $model->identifier = [$this, 'callableC'];
            verify('id should be null', $model->getId(null, null))->null();
        });
    }

    public function callableA($a)
    {
        return 'abc';
    }

    public function callableB($a, $b)
    {
        return 'abc-' . $b;
    }

    public function callableC()
    {
        return null;
    }
}
