<?php
/**
 * @copyright Copyright (c) 2016-2018 Thamtech, LLC
 * @link https://github.com/thamtech/yii2-ratelimiter-advanced
 * @license https://opensource.org/licenses/BSD-3-Clause
 **/

namespace thamtech\ratelimiter;

use Yii;
use yii\base\InvalidArgumentException;
use yii\di\ServiceLocator;
use thamtech\ratelimiter\limit\RateLimit;
use thamtech\ratelimiter\limit\RateLimitResult;
use thamtech\ratelimiter\limit\RateLimitInterface;

/**
 * RateLimiterComponent is a delegate component of [[RateLimiter]]. Do not use
 * it directly.
 *
 * In order for [[RateLimiter]] to inherit from [[yii\base\ActionFilter]] and
 * [[yii\base\Behavior]], it could not be a descendent of [[yii\base\Component]].
 * However, the ideal method of configuring the rate limits, handlers, and
 * events required that it in fact behave like a [[ServiceLocator]] and
 * [[yii\base\Component]].
 *
 * The solution was to let [[RateLimiter]] be an [[yii\base\ActionFilter]] but
 * include magic methods that delegate all other method and property calls to
 * this RateLimiterComponent object, including behavior and event attachments.
 *
 * @author Tyler Ham <tyler@thamtech.com>
 *
 * With some of the interface, documentation, and algorithm borrowed
 * from [[yii\filters\RateLimiter]] by
 * @author Qiang Xue <qiang.xue@gmail.com>
 */
class RateLimiterComponent extends ServiceLocator
{
    /**
     * @var yii\web\Request the current request. If not set, the `request` application
     *     component will be used.
     */
    public $request;

    /**
     * @var RateLimiter the RateLimiter object that delegates to this component
     */
    public $owner;

    /**
     * @var callable|\Closure a callable that provides a scope ID array. The
     * signature of the function should be the following:
     * `function ($rateLimiter, $rateLimit, $context, $rateLimitId)` and
     * should return a scope ID. If not specified, [[getDefaultScopeId()]] is
     * called instead.
     *
     * @see RateLimiterComponent::getDefaultScopeId()
     */
    public $scopeIdProvider;

    /**
     * Constructor.
     *
     * @param array $config name-value pairs that will be used to initialize the
     *     object properties.
     */
    public function __construct($config = [])
    {
        $this->preInit($config);
        parent::__construct($config);
    }

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        if (!is_callable($this->scopeIdProvider)) {
            $this->scopeIdProvider = [$this, 'getDefaultScopeId'];
        }
    }

    /**
     * This method is invoked right before an action is to be executed.
     *
     * @param Action $action the action to be executed.
     *
     * @return bool whether the action should continue to be executed.
     */
    public function beforeAction($action)
    {
        $context = new Context([
            'request' => $this->getRequest(),
            'action' => $action,
        ]);

        $rateLimits = $this->rateLimit->getRateLimits($context);
        $rateLimitResults = $this->checkRateLimits($rateLimits, $context);
        $now = time();

        if (!count($rateLimitResults)) {
            return true;
        }

        $event = new RateLimitsCheckedEvent([
            'time' => $now,
            'context' => $context,
            'rateLimitResults' => $rateLimitResults,
        ]);
        $this->trigger(RateLimiter::EVENT_RATE_LIMITS_CHECKED, $event);

        // check for any rate limits that were exceeded
        $exceededRateLimitResults = array_filter($rateLimitResults, function ($rateLimitResult) {
            return $rateLimitResult->isExceeded;
        });

        if (!count($exceededRateLimitResults)) {
            return true;
        }

        $event = new RateLimitsExceededEvent([
            'time' => $now,
            'context' => $context,
            'rateLimitResults' => $exceededRateLimitResults,
        ]);
        $this->trigger(RateLimiter::EVENT_RATE_LIMITS_EXCEEDED, $event);

        return $event->isValid;
    }

    /**
     * Check the given rate limit to see if it is exceeded.
     *
     * @param  RateLimit $rateLimit the rate limit to check
     *
     * @param Context $context the current request/action context
     *
     * @param string $rateLimitId the ID of the rate limit being checked
     *
     * @return RateLimitResult
     */
    public function checkRateLimit($rateLimit, $context, $rateLimitId)
    {
        $current = time();

        // get limit/window paramters
        $limit = $rateLimit->limit;
        $window = $rateLimit->window;

        // construct key used to scope the allowance data
        $scopeId = call_user_func($this->scopeIdProvider, $this->owner, $rateLimit, $context, $rateLimitId);

        // get allowance parameters
        $allowanceInfo = $this->allowanceStorage->loadAllowance($scopeId, $context);
        $allowance = $allowanceInfo['allowance'];
        $timestamp = $allowanceInfo['timestamp'];

        // leaky bucket algorithm
        $allowance += (int) (($current - $timestamp) * $limit / $window);
        $allowance = min($allowance, $limit); // trim allowance to bucket (window) capacity

        // prepare result array
        if ($allowance < 1) {
            $result = new RateLimitResult([
                'timestamp' => $current,
                'allowance' => 0,
                'isExceeded' => true,
                'rateLimitId' => $rateLimitId,
                'rateLimit' => $rateLimit,
            ]);
        } else {
            $result = new RateLimitResult([
                'timestamp' => $current,
                'allowance' => $allowance - 1,
                'isExceeded' => false,
                'rateLimitId' => $rateLimitId,
                'rateLimit' => $rateLimit,
            ]);
        }

        // persist the updated allowance/timestamp
        $this->allowanceStorage->saveAllowance($scopeId, $context, $result->allowance, $result->timestamp, $window);

        return $result;
    }

    /**
     * Checks if any of the rate limits are excceeded.
     *
     * @param  array $rateLimits IDs as keys and [[RateLimit]] or arrays as values
     *
     * @param Context $context the current request/action context
     *
     * @return array list of [[RateLimitResult]]s keyed on rate limit IDs
     */
    public function checkRateLimits($rateLimits, $context)
    {
        $results = [];

        foreach ($rateLimits as $id => $rateLimit) {
            $rateLimit = $this->asRateLimit($rateLimit, $context);
            if ($rateLimit->isActive($context, $id)) {
                $results[$id] = $this->checkRateLimit($rateLimit, $context, $id);
            }
        }

        return $results;
    }

    /**
     * Ensure that the given rateLimit is a [[RateLimit]]. An array will be
     * passed to [[Yii::createObject]] and checked to make sure the result
     * is a [[RateLimit]] object.
     *
     * @param  RateLimitInterface|array $rateLimit A [[RateLimitInterface]]
     *     object or configuration array
     *
     * @param \thamtech\ratelimiter\Context $context the current request/action
     *     context
     *
     * @return RateLimit
     *
     * @throws InvalidArgumentException
     */
    public function asRateLimit($rateLimit, $context)
    {
        if (is_array($rateLimit)) {
            if (!isset($rateLimit['class'])) {
                $rateLimit['class'] = RateLimit::class;
            }
            $rateLimit = Yii::createObject($rateLimit);
        }

        if ($rateLimit instanceof RateLimitInterface) {
            return $rateLimit->getRateLimit($context);
        }

        throw new InvalidArgumentException('The rateLimit must be an instance of thamtech\ratelimiter\limit\RateLimitInterface or be a configuration array to create one.');
    }

    /**
     * Gets the [[request]] property, or the `request` application component if
     * it is not defined.
     *
     * @return yii\web\Request
     */
    public function getRequest()
    {
        return $this->request ?: Yii::$app->getRequest();
    }

    /**
     * Pre-initialize the configuration array.
     * This method is called at the beginning of the constructor.
     * It initializes core components.
     * If you override this method, please make sure you call the parent implementation.
     *
     * @param  array &$config The RateLimiter configuration
     */
    protected function preInit(&$config)
    {
        // merge coreComponents with custom components
        foreach ($this->coreComponents() as $id => $component) {
            if (!isset($config['components'][$id])) {
                $config['components'][$id] = $component;
            } elseif (is_array($config['components'][$id]) && !isset($config['components'][$id]['class'])) {
                $config['components'][$id]['class'] = $component['class'];
            }
        }
    }

    /**
     * Returns the configuration of core components.
     * @see set()
     */
    protected function coreComponents()
    {
        return [
            'rateLimit' => [
                'class' => 'thamtech\ratelimiter\limit\DefaultRateLimitProvider',
            ],
            'allowanceStorage' => [
                'class' => 'thamtech\ratelimiter\allowance\AllowanceCacheStorage',
                'cache' => 'yii\caching\DummyCache',
            ],
        ];
    }

    /**
     * Gets the default scope ID array
     *
     * @param RateLimiter $rateLimiter the RateLimiter object that delegates to
     * this component
     *
     * @param RateLimit $rateLimit the rate limit
     *
     * @param \thamtech\ratelimiter\Context $context the current request/action
     *     context
     *
     * @param string $rateLimitId The array key that defined the rate limit
     *    in the [[RateLimiter]].\
     *
     * @return string scope ID
     */
    protected function getDefaultScopeId($rateLimiter, $rateLimit, $context, $rateLimitId)
    {
        return implode('-', [
            static::class,
            isset($rateLimiter->owner) ? $rateLimiter->owner->className() : '', // class name of Controller applying the rate limit
            $rateLimitId,
            $rateLimit->getId($context, $rateLimitId),
        ]);
    }
}
