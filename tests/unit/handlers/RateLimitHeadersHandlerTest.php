<?php
/**
 * @copyright Copyright (c) 2016-2018 Thamtech, LLC
 * @link https://github.com/thamtech/yii2-ratelimiter-advanced
 * @license https://opensource.org/licenses/BSD-3-Clause
**/

namespace tests\unit\handlers;

use Yii;
use tests\unit\TestCase;
use Codeception\Specify;
use thamtech\ratelimiter\handlers\RateLimitHeadersHandler;
use thamtech\ratelimiter\limit\RateLimit;
use thamtech\ratelimiter\limit\RateLimitResult;

use thamtech\ratelimiter\RateLimitsCheckedEvent;
use thamtech\ratelimiter\Context;

/**
 * RateLimitHeadersHandler test
 *
 * @author Tyler Ham <tyler@thamtech.com>
 */
class RateLimitHeadersHandlerTest extends TestCase
{
    use Specify;

    public function testOnRateLimitsCheckedDefault()
    {
        $handler = new RateLimitHeadersHandler();
        $event = new RateLimitsCheckedEvent([
            'rateLimitResults' => [
                'user' => new RateLimitResult([
                    'allowance' => 0,
                    'rateLimit' => new RateLimit([
                        'limit' => 1000,
                        'window' => 3600,
                    ]),
                ]),
            ],
        ]);

        $handler->onRateLimitsChecked($event);

        $headers = Yii::$app->getResponse()->getHeaders();
        $this->assertTrue($headers->has('X-Rate-Limit-Limit'));
        $this->assertTrue($headers->has('X-Rate-Limit-Remaining'));
        $this->assertTrue($headers->has('X-Rate-Limit-Reset'));

        $this->assertEquals(1000, $headers->get('X-Rate-Limit-Limit'));
        $this->assertEquals(0, $headers->get('X-Rate-Limit-Remaining'));
        $this->assertEquals(3600, $headers->get('X-Rate-Limit-Reset'));
    }

    public function testOnRateLimitsCheckedMultiplePrefixes()
    {
        $handler = new RateLimitHeadersHandler([
            'prefix' => ['X-Rate-Limit-', 'X-RateLimit-'],
        ]);
        $event = new RateLimitsCheckedEvent([
            'rateLimitResults' => [
                'user' => new RateLimitResult([
                    'allowance' => 0,
                    'rateLimit' => new RateLimit([
                        'limit' => 1000,
                        'window' => 3600,
                    ]),
                ]),
            ],
        ]);

        $handler->onRateLimitsChecked($event);

        $headers = Yii::$app->getResponse()->getHeaders();
        $this->assertTrue($headers->has('X-Rate-Limit-Limit'));
        $this->assertTrue($headers->has('X-Rate-Limit-Remaining'));
        $this->assertTrue($headers->has('X-Rate-Limit-Reset'));

        $this->assertEquals(1000, $headers->get('X-Rate-Limit-Limit'));
        $this->assertEquals(0, $headers->get('X-Rate-Limit-Remaining'));
        $this->assertEquals(3600, $headers->get('X-Rate-Limit-Reset'));

        $this->assertTrue($headers->has('X-RateLimit-Limit'));
        $this->assertTrue($headers->has('X-RateLimit-Remaining'));
        $this->assertTrue($headers->has('X-RateLimit-Reset'));

        $this->assertEquals(1000, $headers->get('X-RateLimit-Limit'));
        $this->assertEquals(0, $headers->get('X-RateLimit-Remaining'));
        $this->assertEquals(3600, $headers->get('X-RateLimit-Reset'));
    }

}
