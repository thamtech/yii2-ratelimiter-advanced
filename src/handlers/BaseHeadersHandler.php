<?php
/**
 * @copyright Copyright (c) 2016-2018 Thamtech, LLC
 * @link https://github.com/thamtech/yii2-ratelimiter-advanced
 * @license https://opensource.org/licenses/BSD-3-Clause
 **/

namespace thamtech\ratelimiter\handlers;

use Yii;

/**
 * BaseHeadersHandler is a base class for handlers wishing to set rate-related
 * HTTP headers.
 *
 * @author Tyler Ham <tyler@thamtech.com>
 */
class BaseHeadersHandler extends BaseHandler
{
    /**
     * @var yii\web\Response the current web response. If not set, the
     *     `response` application component will be used.
     */
    public $response;

    /**
     * Compute combined limit, remaining, reset values for all rateLimits.
     *
     * @param  thamtech\ratelimiter\RateLimitResult[] $rateLimits array associative array keyed on rate limit IDs with
     *     [[thamtech\ratelimiter\RateLimitResult]] as each value
     *
     * @return array with keys limit, remaining, and reset
     */
    protected function computeHeaderValues($rateLimitResults)
    {
        // looking for largest reset; smallest limit; smallest remaining
        $extrema = [
            'limit' => PHP_INT_MAX,
            'remaining' => PHP_INT_MAX,
            'reset' => 0,
            'retryAfter' => 0,
        ];

        foreach ($rateLimitResults as $id => $rateLimitResult) {
            $limit = $rateLimitResult->rateLimit->limit;
            $remaining = $rateLimitResult->allowance;
            $window = $rateLimitResult->rateLimit->window;
            $reset = (int) (($limit - $remaining) * $window / $limit);
            $retryAfter = (int) ceil((1 - $remaining) * $window / $limit);

            $extrema = [
                'limit' => min($extrema['limit'], $limit),
                'remaining' => min($extrema['remaining'], $remaining),
                'reset' => max($extrema['reset'], $reset),
                'retryAfter' => max($extrema['retryAfter'], $retryAfter),
            ];
        }

        return $extrema;
    }

    /**
     * Gets the [[response]] property, or the `response` application component if
     * it is not defined.
     *
     * @return yii\web\Response
     */
    protected function getResponse()
    {
        return $this->response ?: Yii::$app->getResponse();
    }
}
