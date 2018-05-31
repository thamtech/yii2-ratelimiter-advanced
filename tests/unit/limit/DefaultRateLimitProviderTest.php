<?php
/**
 * @copyright Copyright (c) 2016-2018 Thamtech, LLC
 * @link https://github.com/thamtech/yii2-ratelimiter-advanced
 * @license https://opensource.org/licenses/BSD-3-Clause
**/

namespace tests\unit\limit;

use tests\unit\TestCase;
use Codeception\Specify;
use thamtech\ratelimiter\limit\DefaultRateLimitProvider;

/**
 * DefaultRateLimitProvider test
 *
 * @author Tyler Ham <tyler@thamtech.com>
 */
class DefaultRateLimitProviderTest extends TestCase
{
    use Specify;

    public function testGetRateLimits()
    {
        $model = new DefaultRateLimitProvider();
        $this->assertSame($model->definitions, $model->getRateLimits(null));

        $definitions = [
            'ip' => [
                'limit' => 1000,
                'window' => 3600,
                'identifier' => function($context, $rateLimitId) {
                    return $context->request->getUserIP();
                },
            ],
        ];

        $model = new DefaultRateLimitProvider([
            'definitions' => $definitions,
        ]);
        $this->assertSame($definitions, $model->getRateLimits(null));
    }
}
