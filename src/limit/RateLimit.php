<?php
/**
 * @copyright Copyright (c) 2016-2018 Thamtech, LLC
 * @link https://github.com/thamtech/yii2-ratelimiter-advanced
 * @license https://opensource.org/licenses/BSD-3-Clause
 **/

namespace thamtech\ratelimiter\limit;

use yii\base\BaseObject;

/**
 * RateLimit represents the parameters needed to establish a rate limit.
 *
 * This is also the standard implementation of the [[RateLimitInterface]].
 *
 * @author Tyler Ham <tyler@thamtech.com>
 */
class RateLimit extends BaseObject implements RateLimitInterface
{
    /**
     * @var bool|callable Indicates if this rate limit is currently active.
     */
    public $active = true;

    /**
     * @var int Allowed hits per window
     */
    public $limit;

    /**
     * @var int Window duration in seconds
     */
    public $window;

    /**
     * @var string|callable|null An optional identifier which will be used to
     *    scope the rate limit. When null, the rate limit is applied globally.
     *
     *    Otherwise, consider providing a unique identifier such as a user ID or
     *    an IP address to apply a rate per user or per IP address. For example,
     *
     *    ```php
     *    <?php
     *    [
     *        'identifier' => Yii::$app->getRequest()->getUserIP(),
     *    ]
     *    ```
     *
     *    Use a callable to defer providing an identifier. For example,
     *
     *    ```php
     *    <?php
     *    [
     *        'identifier' => function($context, $rateLimitId) {
     *            return $context->request->getUserIP();
     *        },
     *    ]
     *    ```
     */
    public $identifier;

    /**
     * Gets or evaluates the identifier parameter. This along with the
     * rate limit ID will be used to scope rate limit allowances.
     *
     * Scalar identifiers are returned as strings; callables are invoked.
     *
     * @param \thamtech\ratelimiter\Context $context the current request/action
     *     context
     *
     * @param string $rateLimitId The array key that defined the rate limit
     *    in the [[RateLimiter]].
     *
     * @return string|null
     */
    public function getId($context, $rateLimitId)
    {
        if (is_scalar($this->identifier)) {
            return (string) $this->identifier;
        }

        if (is_callable($this->identifier)) {
            $id = call_user_func($this->identifier, $context, $rateLimitId);
            if ($id === null) {
                return null;
            }

            return (string) $id;
        }

        return null;
    }

    /**
     * Indicates whether or not this rate limit should be checked.
     *
     * @return bool whether or not this rate limit is active
     */
    public function isActive($context, $rateLimitId)
    {
        if (is_scalar($this->active)) {
            return (bool) $this->active;
        }

        if (is_callable($this->active)) {
            return (bool) call_user_func($this->active, $context, $rateLimitId);
        }

        return true; // active by default
    }

    /**
     * @inheritdoc
     */
    public function getRateLimit($context)
    {
        return $this;
    }
}
