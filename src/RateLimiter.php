<?php
/**
 * @copyright Copyright (c) 2016-2018 Thamtech, LLC
 * @link https://github.com/thamtech/yii2-ratelimiter-advanced
 * @license https://opensource.org/licenses/BSD-3-Clause
 **/

namespace thamtech\ratelimiter;

use Yii;
use yii\base\ActionFilter;
use yii\base\Component;
use yii\base\UnknownMethodException;

/**
 * RateLimiter implements a rate limiting algorithm based on the
 * [leaky bucket algorithm](http://en.wikipedia.org/wiki/Leaky_bucket).
 *
 * ## Implementation Notes
 *
 * Due to [[ActionFilter]] not being a descendent of [[yii\di\ServiceLocator]]
 * or [[yii\base\Component]], the implementation is not entirely straightforward.
 *
 * All of the actual rate-limiting implementation is contained within a
 * [[RateLimiterComponent]] object, which this class delegates to.  For most
 * purposes, treat the RateLimiter as if it were both an [[ActionFilter]]
 * and a [[RateLimiterComponent]]. The magic methods implemented in this
 * class are essentially simulating multiple-inheritance to a certain extent.
 *
 * @author Tyler Ham <tyler@thamtech.com>
 */
class RateLimiter extends ActionFilter
{
    const EVENT_RATE_LIMITS_EXCEEDED = 'rateLimitsExceeded';
    const EVENT_RATE_LIMITS_CHECKED = 'rateLimitsChecked';

    /**
     * @var RateLimiterComponent delegate RateLimiter component
     */
    private $component;

    /**
     * Constructor.
     *
     * @param array $config name-value pairs that will be used to initialize the
     *     object properties.
     */
    public function __construct($config = [])
    {
        // split out config between this ActionFilter and the RateLimiterComponent
        $thisConfig = $this->preInit($config);
        $componentConfig = $this->preInitComponent($config);

        $this->component = Yii::createObject($componentConfig);

        parent::__construct($thisConfig);
    }

    /**
     * Sets the value of a component property.
     * This method will check if the property is available in the component and
     * attempt to set it there.
     *
     * Do not call this method directly as it is a PHP magic method that
     * will be implicitly called when executing `$component->property = $value;`.
     *
     * @param string $name the property name or the event name
     *
     * @param mixed $value the property value
     *
     * @throws UnknownPropertyException if the property is not defined
     *
     * @throws InvalidCallException if the property is read-only.
     *
     * @see __get()
     */
    public function __set($name, $value)
    {
        if ($this->component->canSetProperty($name)) {
            $this->component->$name = $value;

            return;
        }

        parent::__set($name, $value);
    }

    /**
     * @inheritdoc
     */
    public function beforeAction($action)
    {
        return $this->component->beforeAction($action);
    }

    /**
     * Returns the value of a component property.
     * This method will check if the property is available in the component and
     * attempt to get it.
     *
     * Do not call this method directly as it is a PHP magic method that
     * will be implicitly called when executing `$value = $component->property;`.
     *
     * @param string $name the property name
     *
     * @return mixed the property value or the value of a behavior's property
     *
     * @throws UnknownPropertyException if the property is not defined
     *
     * @throws InvalidCallException if the property is write-only.
     *
     * @see __set()
     */
    public function __get($name)
    {
        if ($this->component->canGetProperty($name)) {
            return $this->component->$name;
        } elseif ($this->component->has($name)) {
            return $this->component->get($name);
        }

        return parent::__get($name);
    }

    /**
     * Checks if a property is set, i.e. defined and not null.
     * This method will check if the property is set in the component.
     *
     * Do not call this method directly as it is a PHP magic method that
     * will be implicitly called when executing `isset($component->property)`.
     *
     * @param string $name the property name or the event name
     *
     * @return bool whether the named property is set
     *
     * @see http://php.net/manual/en/function.isset.php
     */
    public function __isset($name)
    {
        if (isset($this->component->$name)) {
            return true;
        }

        return parent::__isset($name);
    }

    /**
     * Sets a component property to be null.
     * This method will attempt to unset a property in the component.
     *
     * Do not call this method directly as it is a PHP magic method that
     * will be implicitly called when executing `unset($component->property)`.
     *
     * @param string $name the property name
     *
     * @throws InvalidCallException if the property is read only.
     *
     * @see http://php.net/manual/en/function.unset.php
     */
    public function __unset($name)
    {
        if (isset($this->component->$name)) {
            unset($this->component->$name);

            return;
        }

        return parent::__unset($name);
    }

    /**
     * Calls the named method which is not a class method.
     *
     * This method will check if the RateLimiterComponent has
     * the named method and will execute it if available.
     *
     * Do not call this method directly as it is a PHP magic method that
     * will be implicitly called when an unknown method is being invoked.
     *
     * @param string $name the method name
     *
     * @param array $params method parameters
     *
     * @return mixed the method return value
     *
     * @throws UnknownMethodException when calling unknown method
     */
    public function __call($name, $params)
    {
        if ($this->component->hasMethod($name)) {
            return call_user_func_array([$this->component, $name], $params);
        }
        throw new UnknownMethodException('Calling unknown method: ' . get_class($this) . "::$name()");
    }

    private function preInitComponent($config)
    {
        $componentConfig = [];
        foreach ($config as $key => $value) {
            if (!in_array($key, ['except', 'only', 'owner'])) {
                $componentConfig[$key] = $value;
            }
        }
        $componentConfig['class'] = RateLimiterComponent::class;
        $componentConfig['owner'] = $this;

        return $componentConfig;
    }

    private function preInit($config)
    {
        $thisConfig = [];
        foreach (['except', 'only', 'owner'] as $key) {
            if (isset($config[$key])) {
                $thisConfig[$key] = $config[$key];
            }
        }

        return $thisConfig;
    }
}
