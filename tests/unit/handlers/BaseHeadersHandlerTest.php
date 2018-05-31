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
use thamtech\ratelimiter\handlers\BaseHeadersHandler;
use thamtech\ratelimiter\limit\RateLimit;
use thamtech\ratelimiter\limit\RateLimitResult;

class BaseHeadersHandlerTest extends TestCase
{
    use Specify;

    public function testComputeHeaderValuesSingle()
    {
        $rateLimitResults = [
            'user' => new RateLimitResult([
                'allowance' => 0,
                'rateLimit' => new RateLimit([
                    'limit' => 1000,
                    'window' => 3600,
                ]),
            ]),
        ];

        $handler = new TestableBaseHeadersHandler();
        $values = $handler->computeHeaderValues($rateLimitResults);

        $this->assertEquals(1000, $values['limit']);
        $this->assertEquals(0, $values['remaining']);
        $this->assertEquals(3600, $values['reset']);
        $this->assertEquals(4, $values['retryAfter']);
    }

    public function testComputeHeaderValuesCombined()
    {
        $handler = new TestableBaseHeadersHandler();

        $rateLimitResults = [
            'user' => new RateLimitResult([
                'allowance' => 0,
                'rateLimit' => new RateLimit([
                    'limit' => 1000,
                    'window' => 3600,
                ]),
            ]),
            'ip' => new RateLimitResult([
                'allowance' => 0,
                'rateLimit' => new RateLimit([
                    'limit' => 100,
                    'window' => 7200,
                ]),
            ]),
        ];

        $values = $handler->computeHeaderValues($rateLimitResults);

        $this->assertEquals(100, $values['limit']);
        $this->assertEquals(0, $values['remaining']);
        $this->assertEquals(7200, $values['reset']);
        $this->assertEquals(72, $values['retryAfter']);



        $rateLimitResults = [
            'user' => new RateLimitResult([
                'allowance' => 25,
                'rateLimit' => new RateLimit([
                    'limit' => 1000,
                    'window' => 3600,
                ]),
            ]),
            'ip' => new RateLimitResult([
                'allowance' => 75,
                'rateLimit' => new RateLimit([
                    'limit' => 100,
                    'window' => 7200,
                ]),
            ]),
        ];

        $values = $handler->computeHeaderValues($rateLimitResults);

        $this->assertEquals(100, $values['limit']);
        $this->assertEquals(25, $values['remaining']);
        $this->assertEquals(3510, $values['reset']);
        $this->assertEquals(0, $values['retryAfter']);
    }
}

class TestableBaseHeadersHandler extends BaseHeadersHandler
{
    public function computeHeaderValues($rateLimits)
    {
        return parent::computeHeaderValues($rateLimits);
    }
}
