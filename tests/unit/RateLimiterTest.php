<?php
/**
 * @copyright Copyright (c) 2016-2018 Thamtech, LLC
 * @link https://github.com/thamtech/yii2-ratelimiter-advanced
 * @license https://opensource.org/licenses/BSD-3-Clause
**/

namespace tests\unit;

use Yii;
use tests\unit\TestCase;
use Codeception\Specify;
use thamtech\ratelimiter\RateLimiter;
use thamtech\ratelimiter\Context;
use thamtech\ratelimiter\limit\RateLimit;
use thamtech\ratelimiter\limit\RateLimitInterface;

/**
 * RateLimiter test
 *
 * @author Tyler Ham <tyler@thamtech.com>
 */
class RateLimiterTest extends TestCase
{
    use Specify;

    public function testBeforeAction()
    {
        $definitions = [
            'ip' => [
                'limit' => 100,
                'window' => 7200,
                'identifier' => function($context, $rateLimitId) {
                    return $context->request->getUserIP();
                },
            ],
            'user' => [
                'limit' => 1000,
                'window' => 3600,
                'identifier' => 1,
            ],
        ];

        $rateLimiter = new RateLimiter([
            'components' => [
                'allowanceStorage' => [
                    'cache' => 'yii\caching\ArrayCache',
                ],
                'rateLimit' => [
                    'definitions' => $definitions,
                ],
                'as tooManyRequestsException' => [
                    'class' => 'thamtech\ratelimiter\handlers\TooManyRequestsHttpExceptionHandler',
                ],
            ],
        ]);

        $this->assertTrue($rateLimiter->beforeAction(null));
    }

    public function testBeforeActionEmpty()
    {
        $definitions = [
        ];

        $rateLimiter = new RateLimiter([
            'components' => [
                'allowanceStorage' => [
                    'cache' => 'yii\caching\ArrayCache',
                ],
                'rateLimit' => [
                    'definitions' => $definitions,
                ],
                'as tooManyRequestsException' => [
                    'class' => 'thamtech\ratelimiter\handlers\TooManyRequestsHttpExceptionHandler',
                ],
            ],
        ]);

        $this->assertTrue($rateLimiter->beforeAction(null));
    }

    public function testBeforeActionSkipped()
    {
        $definitions = [
            'ip' => [
                'limit' => 100,
                'window' => 7200,
                'identifier' => function($context, $rateLimitId) {
                    return $context->request->getUserIP();
                },
            ],
            'user' => [
                'limit' => 1000,
                'window' => 3600,
                'identifier' => 1,
                'active' => false,
            ],
        ];

        $rateLimiter = new RateLimiter([
            'components' => [
                'allowanceStorage' => [
                    'cache' => 'yii\caching\ArrayCache',
                ],
                'rateLimit' => [
                    'definitions' => $definitions,
                ],
            ],
            'as tooManyRequestsException' => [
                'class' => 'thamtech\ratelimiter\handlers\TooManyRequestsHttpExceptionHandler',
                'message' => 'This is a test TooManyRequestsHttpException message.'
            ],
        ]);

        $context = new Context();

        // set allowance to 0 in storage; this 'user' limit is exceeded
        $rateLimiter->allowanceStorage->saveAllowance(
            'thamtech\ratelimiter\RateLimiterComponent--user-1',
            $context,
            0,
            time() - 10, // 10 seconds ago
            3600
        );

        $this->assertTrue($rateLimiter->beforeAction(null));
    }

    /**
     * @expectedException yii\web\TooManyRequestsHttpException
     * @expectedExceptionMessage This is a test TooManyRequestsHttpException message.
     */
    public function testBeforeActionException()
    {
        $definitions = [
            'ip' => [
                'limit' => 100,
                'window' => 7200,
                'identifier' => function($context, $rateLimitId) {
                    return $context->request->getUserIP();
                },
            ],
            'user' => [
                'limit' => 1000,
                'window' => 3600,
                'identifier' => 1,
            ],
        ];

        $rateLimiter = new RateLimiter([
            'components' => [
                'allowanceStorage' => [
                    'cache' => 'yii\caching\ArrayCache',
                ],
                'rateLimit' => [
                    'definitions' => $definitions,
                ],
            ],
            'as tooManyRequestsException' => [
                'class' => 'thamtech\ratelimiter\handlers\TooManyRequestsHttpExceptionHandler',
                'message' => 'This is a test TooManyRequestsHttpException message.'
            ],
        ]);

        $context = new Context();

        // set allowance to 0 in storage; this 'user' limit is exceeded
        $rateLimiter->allowanceStorage->saveAllowance(
            'thamtech\ratelimiter\RateLimiterComponent--user-1',
            $context,
            0,
            time() - 10, // 10 seconds ago
            3600
        );

        $rateLimiter->beforeAction(null);
    }

    /**
     * @expectedException yii\web\TooManyRequestsHttpException
     * @expectedExceptionMessage This is a test TooManyRequestsHttpException message.
     */
    public function testBeforeActionExceptionWithOwner()
    {
        $definitions = [
            'ip' => [
                'limit' => 100,
                'window' => 7200,
                'identifier' => function($context, $rateLimitId) {
                    return $context->request->getUserIP();
                },
            ],
            'user' => [
                'limit' => 1000,
                'window' => 3600,
                'identifier' => 1,
            ],
        ];

        $rateLimiter = new RateLimiter([
            'components' => [
                'allowanceStorage' => [
                    'cache' => 'yii\caching\ArrayCache',
                ],
                'rateLimit' => [
                    'definitions' => $definitions,
                ],
            ],
            'owner' => new Context(), // not normal usage; just testing to make sure it picks up the className of the owner object
            'as tooManyRequestsException' => [
                'class' => 'thamtech\ratelimiter\handlers\TooManyRequestsHttpExceptionHandler',
                'message' => 'This is a test TooManyRequestsHttpException message.'
            ],
        ]);

        $context = new Context();

        // set allowance to 0 in storage; this 'user' limit is exceeded
        $rateLimiter->allowanceStorage->saveAllowance(
            'thamtech\ratelimiter\RateLimiterComponent-thamtech\ratelimiter\Context-user-1',
            $context,
            0,
            time() - 10, // 10 seconds ago
            3600
        );

        $rateLimiter->beforeAction(null);
    }

    public function testBeforeActionBlocked()
    {
        $definitions = [
            'ip' => [
                'limit' => 100,
                'window' => 7200,
                'identifier' => function($context, $rateLimitId) {
                    return $context->request->getUserIP();
                },
            ],
            'user' => [
                'limit' => 1000,
                'window' => 3600,
                'identifier' => 1,
            ],
        ];

        $rateLimiter = new RateLimiter([
            'components' => [
                'allowanceStorage' => [
                    'cache' => 'yii\caching\ArrayCache',
                ],
                'rateLimit' => [
                    'definitions' => $definitions,
                ],
            ],
            'on rateLimitsExceeded' => function($event) {
                $event->isValid = false;
            },
        ]);

        $context = new Context();

        // set allowance to 0 in storage; this 'user' limit is exceeded
        $rateLimiter->allowanceStorage->saveAllowance(
            'thamtech\ratelimiter\RateLimiterComponent--user-1',
            $context,
            0,
            time() - 10, // 10 seconds ago
            3600
        );

        $this->assertFalse($rateLimiter->beforeAction(null));
    }

    public function testConstructDefault()
    {
        $rateLimiter = new RateLimiter();

        $this->specify('default construction initializes default components', function () use ($rateLimiter) {
            verify('has allowanceStorage component', $rateLimiter->has('allowanceStorage'))->true();
            verify('allowanceStorage instance of AllowanceCacheStorage', $rateLimiter->allowanceStorage)->isInstanceOf('thamtech\ratelimiter\allowance\AllowanceCacheStorage');
            verify('cache instance of DummyCache', $rateLimiter->allowanceStorage->cache)->equals('yii\caching\DummyCache');

            verify('has rateLimit component', $rateLimiter->has('rateLimit'))->true();
            verify('rateLimit instanceof DefaultRateLimitProvider', $rateLimiter->rateLimit)->isInstanceOf('thamtech\ratelimiter\limit\DefaultRateLimitProvider');
        });
    }

    public function testConstructSpecified()
    {
        $definitions = [
            'ip' => [
                'limit' => 1000,
                'window' => 3600,
                'identifier' => function($context, $rateLimitId) {
                    return $context->request->getUserIP();
                },
            ],
        ];

        $rateLimiter = new RateLimiter([
            'components' => [
                'allowanceStorage' => [
                    'cache' => 'yii\caching\ArrayCache',
                ],
                'rateLimit' => [
                    'definitions' => $definitions,
                ],
            ],
        ]);

        $this->specify('specified construction merges config', function () use ($rateLimiter, $definitions) {
            verify('has allowanceStorage component', $rateLimiter->has('allowanceStorage'))->true();
            verify('allowanceStorage instance of AllowanceCacheStorage', $rateLimiter->allowanceStorage)->isInstanceOf('thamtech\ratelimiter\allowance\AllowanceCacheStorage');
            verify('cache instance of ArrayCache', $rateLimiter->allowanceStorage->cache)->equals('yii\caching\ArrayCache');

            verify('has rateLimit component', $rateLimiter->has('rateLimit'))->true();
            verify('rateLimit instanceof DefaultRateLimitProvider', $rateLimiter->rateLimit)->isInstanceOf('thamtech\ratelimiter\limit\DefaultRateLimitProvider');
            verify('has rateLimit definitions', $rateLimiter->rateLimit->getRateLimits(null))->same($definitions);
        });
    }

    public function testGetRequestEmpty()
    {
        $rateLimiter = new TestableRateLimiter();

        $this->specify('empty request should yield application request component', function () use ($rateLimiter) {
            verify('returns application request component', $rateLimiter->getRequest())->same(Yii::$app->getRequest());
        });

        $obj = Yii::createObject('yii\caching\DummyCache'); // any old object is fine for testing
        $rateLimiter = new TestableRateLimiter([
            'request' => $obj,
        ]);
        $this->specify('populated request should yield populated object', function () use ($rateLimiter, $obj) {
            verify('returns populated object', $rateLimiter->getRequest())->same($obj);
        });
    }

    /**
     * @expectedException yii\base\InvalidParamException
     * @expectedExceptionMessage The rateLimit must be an instance of thamtech\ratelimiter\limit\RateLimitInterface or be a configuration array to create one.
     */
    public function testAsRateLimitBadConfig()
    {
        $rateLimiter = new TestableRateLimiter();
        $rateLimiter->asRateLimit([
            'class' => 'yii\caching\DummyCache', // any valid object that is NOT a RateLimitInterface
        ], null);
    }

    /**
     * @expectedException yii\base\InvalidParamException
     * @expectedExceptionMessage The rateLimit must be an instance of thamtech\ratelimiter\limit\RateLimitInterface or be a configuration array to create one.
     */
    public function testAsRateLimitBadObject()
    {
        $rateLimiter = new TestableRateLimiter();
        $obj = Yii::createObject('yii\caching\DummyCache'); // any valid object that is NOT a RateLimitInterface
        $rateLimiter->asRateLimit($obj, null);
    }

    public function testAsRateLimit()
    {
        $rateLimiter = new TestableRateLimiter();

        $this->specify('array without class should yield a RateLimitInterface object', function () use ($rateLimiter) {
            $arr = [
                'limit' => 1000,
                'window' => 3600,
            ];
            verify('returns a RateLimitInterface object', $rateLimiter->asRateLimit($arr, null))->isInstanceOf('thamtech\ratelimiter\limit\RateLimitInterface');

            $rateLimit = new RateLimit([
                'limit' => 1000,
                'window' => 3600,
            ]);
            verify('returns the given RateLimit object', $rateLimiter->asRateLimit($rateLimit, null))->same($rateLimit);
        });
    }

    public function testCheckRateLimitOk()
    {
        $rateLimiter = new TestableRateLimiter([
            'components' => [
                'allowanceStorage' => [
                    'cache' => 'yii\caching\ArrayCache',
                ],
            ],
        ]);

        $rateLimit = new RateLimit([
            'limit' => 1000,
            'window' => 3600,
            'identifier' => 1
        ]);
        $context = new Context();

        $result = $rateLimiter->checkRateLimit($rateLimit, $context, 'user'); // made up rate limit id 'user'
        $this->assertFalse($result->isExceeded);
        $this->assertGreaterThan(0, $result->allowance);
        $this->assertEquals(time(), $result->timestamp);
        $this->assertSame($rateLimit, $result->rateLimit);
    }

    public function testCheckRateLimitExceeded()
    {
        $rateLimiter = new TestableRateLimiter([
            'components' => [
                'allowanceStorage' => [
                    'cache' => 'yii\caching\ArrayCache',
                ],
            ],
        ]);
        $context = new Context();

        // set allowance to 0 in storage; this 'user' limit is exceeded
        $rateLimiter->allowanceStorage->saveAllowance(
            'thamtech\ratelimiter\RateLimiterComponent--user-1',
            $context,
            0,
            time() - 10, // 10 seconds ago
            3600
        );

        $rateLimit = new RateLimit([
            'limit' => 1, // a hit from 10 secons ago will easily exceed
                         // this rate of 1 per hour
            'window' => 3600,
            'identifier' => 1
        ]);

        $result = $rateLimiter->checkRateLimit($rateLimit, $context, 'user'); // made up rate limit id 'user'
        $this->assertTrue($result->isExceeded);
        $this->assertEquals(0, $result->allowance);
        $this->assertEquals(time(), $result->timestamp);
        $this->assertSame($rateLimit, $result->rateLimit);
    }

    public function testCheckRateLimits()
    {
        // testing two rates, one exceeded

        $rateLimiter = new TestableRateLimiter([
            'components' => [
                'allowanceStorage' => [
                    'cache' => 'yii\caching\ArrayCache',
                ],
            ],
        ]);
        $context = new Context();

        // set allowance to 0 in storage; this 'user' limit is exceeded
        $rateLimiter->allowanceStorage->saveAllowance(
            'thamtech\ratelimiter\RateLimiterComponent--user-1',
            $context,
            0,
            time() - 10, // 10 seconds ago
            3600
        );

        $rateLimits = [
            'user' => new RateLimit([
                'limit' => 1,
                'window' => 3600,
                'identifier' => 1,
            ]),
            'ip' => new RateLimit([
                'limit' => 1000,
                'window' => 3600,
                'identifier' => '127.0.0.1',
            ]),
        ];

        $rateLimitResults = $rateLimiter->checkRateLimits($rateLimits, $context);

        $this->assertCount(2, $rateLimitResults);

        $this->assertFalse($rateLimitResults['ip']->isExceeded);
        $this->assertEquals(1000-1, $rateLimitResults['ip']->allowance);
        $this->assertEquals(time(), $rateLimitResults['ip']->timestamp);
        $this->assertSame($rateLimits['ip'], $rateLimitResults['ip']->rateLimit);

        $this->assertTrue($rateLimitResults['user']->isExceeded);
        $this->assertEquals(0, $rateLimitResults['user']->allowance);
        $this->assertEquals(time(), $rateLimitResults['user']->timestamp);
        $this->assertSame($rateLimits['user'], $rateLimitResults['user']->rateLimit);
    }
}

class TestableRateLimiter extends RateLimiter
{
    public function checkRateLimit($rateLimit, $context, $rateLimitId)
    {
        return parent::checkRatelimit($rateLimit, $context, $rateLimitId);
    }

    public function checkRateLimits($rateLimits, $context)
    {
        return parent::checkRateLimits($rateLimits, $context);
    }

    public function asRateLimit($rateLimit, $context)
    {
        return parent::asRateLimit($rateLimit, $context);
    }

    public function getRequest()
    {
        return parent::getRequest();
    }
}
