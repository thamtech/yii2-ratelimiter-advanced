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
use thamtech\ratelimiter\handlers\RetryAfterHeaderHandler;
use thamtech\ratelimiter\limit\RateLimit;
use thamtech\ratelimiter\limit\RateLimitResult;
use thamtech\ratelimiter\RateLimitsExceededEvent;

class RetryAfterHeaderHandlerTest extends TestCase
{
    use Specify;

    public function testOnRateLimitsExceededDefault()
    {
        $handler = new RetryAfterHeaderHandler();
        $event = new RateLimitsExceededEvent([
            'rateLimitResults' => [
                'user' => new RateLimitResult([
                    'allowance' => 0,
                    'rateLimit' => new RateLimit([
                        'limit' => 1000,
                        'window' => 4000,
                    ]),
                ]),
            ],
        ]);

        $handler->onRateLimitsExceeded($event);

        $headers = Yii::$app->getResponse()->getHeaders();
        $this->assertTrue($headers->has('Retry-After'));
        $this->assertEquals(4, $headers->get('Retry-After'));
    }

    public function testOnRateLimitsExceededIncorrect()
    {
        $handler = new RetryAfterHeaderHandler();
        $event = new RateLimitsExceededEvent([
            'rateLimitResults' => [
                'user' => new RateLimitResult([
                    'allowance' => 1, // if there is still an allowance, the
                                      // rate actually wasn't exceeded and
                                      // Retry-After would be calculated as 0,
                                      // so do not include the header
                    'rateLimit' => new RateLimit([
                        'limit' => 1000,
                        'window' => 4000,
                    ]),
                ]),
            ],
        ]);

        $handler->onRateLimitsExceeded($event);

        $headers = Yii::$app->getResponse()->getHeaders();
        $this->assertFalse($headers->has('Retry-After'));
    }
}
