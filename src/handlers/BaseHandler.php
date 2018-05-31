<?php
/**
 * @copyright Copyright (c) 2016-2018 Thamtech, LLC
 * @link https://github.com/thamtech/yii2-ratelimiter-advanced
 * @license https://opensource.org/licenses/BSD-3-Clause
 **/

namespace thamtech\ratelimiter\handlers;

use yii\base\Behavior;
use thamtech\ratelimiter\RateLimiter;

/**
 * BaseHandler is the base class for rate limit handlers.
 *
 * A rate limit handler will attach itself as an event handler
 * on the owner [[thamtech\ratelimiter\RateLimiter]] object.
 *
 * See implementations of [[RateLimitHeadersHandler]] and
 * [[TooManyRequestsHttpExceptionHandler]] as examples of how to extend this class.
 *
 * @author Tyler Ham <tyler@thamtech.com>
 */
class BaseHandler extends Behavior
{
    /**
     * @var array list of rate limit IDs that this handler should apply
     * to. If this property is not set, then the handler applies to all
     * exceeded rate limit IDs, unless they are listed in [[except]].
     * If a rate limit ID appears in both [[only]] and [[except]], the
     * handler will NOT apply to it.
     *
     * An event can have multiple rate limit IDs that were exceeded. If
     * any one of them matches the [[only]]/[[except]] rules, then the
     * handler will apply to the event.
     *
     * @see except
     */
    public $only;

    /**
     * @var array list of rate limit IDs that this handler should not apply
     * to.
     * @see only
     */
    public $except = [];

    /**
     * @inheritdoc
     */
    public function attach($owner)
    {
        $this->owner = $owner;
        $owner->on(RateLimiter::EVENT_RATE_LIMITS_EXCEEDED, [$this, 'exceededHandler']);
        $owner->on(RateLimiter::EVENT_RATE_LIMITS_CHECKED, [$this, 'checkedHandler']);
    }

    /**
     * @inheritdoc
     */
    public function detach()
    {
        if ($this->owner) {
            $this->owner->off(RateLimiter::EVENT_RATE_LIMITS_EXCEEDED, [$this, 'exceededHandler']);
            $this->owner->off(RateLimiter::EVENT_RATE_LIMITS_CHECKED, [$this, 'checkedHandler']);
            $this->owner = null;
        }
    }

    /**
     * @param RateLimitsExceededEvent $event
     */
    public function exceededHandler($event)
    {
        if (!$this->isActive(array_keys($event->rateLimitResults))) {
            return;
        }

        $this->onRateLimitsExceeded($event);
    }

    /**
     * @param RateLimitsCheckedEvent $event
     */
    public function checkedHandler($event)
    {
        if (!$this->isActive(array_keys($event->rateLimitResults))) {
            return;
        }

        $this->onRateLimitsChecked($event);
    }

    /**
     * This method is invoked when an applicable RateLimitsCheckedEvent
     * is being handled by this handler.
     *
     * You may override this method to handle the event.
     *
     * @param RateLimitsCheckedEvent $event
     */
    public function onRateLimitsChecked($event)
    {
        return;
    }

    /**
     * This method is invoked when an applicable RateLimitsExceededEvent
     * is being handled by this handler.
     *
     * You may override this method to handle the event.
     *
     * @param RateLimitsExceededEvent $event
     */
    public function onRateLimitsExceeded($event)
    {
        return;
    }

    /**
     * Returns a value indicating whether the handler is active for the
     * given exceeded rate limit IDs.
     *
     * @param  string[] $rateLimitIds List of exceeded rate limit IDs
     *
     * @return bool whether the handler is active for the given exceeded
     *     rate limit IDs
     */
    protected function isActive($rateLimitIds)
    {
        $isActive = false;

        foreach ($rateLimitIds as $id) {
            if (!in_array($id, $this->except, true) && (empty($this->only) || in_array($id, $this->only, true))) {
                $isActive = true;
            }
        }

        return $isActive;
    }
}
