Yii2 Advanced Rate Limiter
==========================

Advanced Rate Limiter is a [Yii2's](http://www.yiiframework.com) filter to
enforce or monitor request rate limits.

In contrast to Yii2's built-in [RateLimiter](http://www.yiiframework.com/doc-2.0/guide-rest-rate-limiting.html),
Advanced Rate Limiter:

* allows you to define multiple, independent rate limit definitions
   * by controller action, and
   * by an identifier such as an IP address, user ID, other identifiers relevant
     to your application, or a combination thereof;
* provides support for customizing the type of response to a checked or
  exceeded rate limit such as:
   * sending a `429 Too Many Requests` HTTP response,
   * triggering a Yii2 [Event](http://www.yiiframework.com/doc-2.0/guide-concept-events.html),
   * setting rate-limit HTTP headers, and/or
   * executing your own [Callable](http://php.net/manual/en/language.types.callable.php) or
     [anonymous function](http://php.net/manual/en/functions.anonymous.php);
* provides support for storing and managing the `allowance` and `timestamp`
  values for each rate limit (Yii2's built-in RateLimiter requires you to
  implement the storage yourself);
* allows you to customize the prefix of rate-limit HTTP headers instead of the
  hardcoded `X-Rate-Limit-` prefix used by the built-in `RateLimiter`;
* provides support for sending the `Retry-After` HTTP header indicating how many
  seconds the client should wait before retrying.

For license information check the [LICENSE](LICENSE.md)-file.


Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

```
php composer.phar require --prefer-dist thamtech/yii2-ratelimiter-advanced
```

or add

```
"thamtech/yii2-ratelimiter-advanced": "*"
```

to the `require` section of your `composer.json` file.


Usage
-----

### Introduction

This Rate Limiter is an implementation of the
[leaky bucket algorithm](http://en.wikipedia.org/wiki/Leaky_bucket).

In general, you will configure the rate limiter as a behavior on
any Controller class you want to rate limit. For example,

```php
<?php
public function behaviors()
{
    $behaviors = parent::behaviors();
    $behaviors['rateLimiter'] = [
        'class' => 'thamtech\ratelimiter\RateLimiter',
        'components' => [
            'rateLimit' => [
                'definitions' => [
                    'ip' => [
                        'limit' => 1000, // allowed hits per window
                        'window' => 3600, // window in seconds
                        
                        // Callable or anonymous function returning some unique
                        // identifier. A separate allowance will be tracked for
                        // each identifier.
                        // 
                        // Leave unset to make such a rate apply globally
                        // to all requests coming in through the controller.
                        // 
                        // @param \thamtech\ratelimiter\Context $context the current
                        //     request/action context
                        // 
                        // @param string $rateLimitId The array key that defined the
                        //     rate limit ("ip" in this case)
                        'identifier' => function($context, $rateLimitId) {
                            return $context->request->getUserIP();
                        }
                    ],
                ],
            ],
            'allowanceStorage' => [
                'cache' => 'cache', // use Yii::$app->cache component
            ],
        ],
        'as rateLimitHeaders' => [
            'class' => 'thamtech\ratelimiter\handlers\RateLimitHeadersHandler',
            
            // This can be a single string prefix, or an array of strings to duplicate
            // the headers with multiple prefixes.
            // The default prefix is 'X-Rate-Limit-' if this property is not specified
            'prefix' => ['X-Rate-Limit-', 'X-RateLimit-'],
        ],
        'as retryAfterHeader' => 'thamtech\ratelimiter\handlers\RateLimitHeadersHandler',
        'as tooManyRequestsException' => 'thamtech\ratelimiter\handlers\TooManyRequestsHttpExceptionHandler',
    ];
    return $behaviors;
}
```

Advanced Example:

```php
<?php
public function behaviors()
{
    $behaviors = parent::behaviors();
    $behaviors['rateLimiter'] = [
        'class' => 'thamtech\ratelimiter\RateLimiter',
        
        // except and only work to limit the controller actions on which the
        // rate limiter applies
        'only' => ['login', 'register', 'info'],
        'except' => ['info'],
        
        'components' => [
            'rateLimit' => [
                // class explicitly set, but defaults to this value otherwise
                // 
                // you could provide your own implementation of
                // RateLimitProviderInterface instead
                'class' => 'thamtech\ratelimiter\limit\DefaultRateLimitProvider',
                
                'definitions' => [
                    'user' => 'app\models\User', // implements RateLimitInterface
                    
                    'ip' => [
                        'class' => 'thamtech\ratelimiter\limit\RateLimit',
                        'limit' => 1000, // allowed hits per window
                        'window' => 3600, // window in seconds
                        
                        // Callable or anonymous function returning some unique
                        // identifier. A separate allowance will be tracked for
                        // each identifier.
                        // 
                        // Leave unset to make such a rate apply globally
                        // to all requests coming in through the controller.
                        // 
                        // @param \thamtech\ratelimiter\Context $context the current
                        //     request/action context
                        // 
                        // @param string $rateLimitId The array key that defined the
                        //     rate limit ("ip" in this case)
                        'identifier' => function($context, $rateLimitId) {
                            return $context->request->getUserIP();
                        }
                    ],
                    
                    'user-admin' => [
                        'limit' => 1000,
                        'window' => 3600,
                        'identifier' => Yii::$app->user->getIdentity()->id,
                        
                        // make a rate limit only be considered under certain conditions
                        'active' => Yii::$app->user->getIdentity()->isAdmin(),
                    ],
                ],
            ],
            'allowanceStorage' => [
                'cache' => 'cache', // use Yii::$app->cache component
                
                // The cache key will be made up of:
                //   {cacheKeyPrefix - defaults to 'allowance'}
                //   AllowanceCacheStorage::className() {or other storage component you might use}
                //   RateLimiterComponent::className()
                //   {your controller class}::className()
                //   {rate limit id, like "ip" or "User" in this example}
                //   {identifier, like 192.168.1.1 in this example}
                //   
                // The combination above already makes the key fairly specific to the
                // desired scope, so you probably don't need to do anything
                // special with this default value in most cases.
                'cacheKeyPrefix' => 'allowance',
            ],
        ],
        'as rateLimitHeaders' => [
            'class' => 'thamtech\ratelimiter\handlers\RateLimitHeadersHandler',
            
            // list of rateLimits to ignore
            'except' => ['user'],
            
            // This can be a single string prefix, or an array of strings to duplicate
            // the headers with multiple prefixes.
            // The default prefix is 'X-Rate-Limit-' if this property is not specified
            'prefix' => ['X-Rate-Limit-', 'X-RateLimit-'],
        ],
        
        'as retryAfterHeader' => [
            'class' => 'thamtech\ratelimiter\handlers\RetryAfterHeaderHandler',
            
            // default's to 'Retry-After' if not set
            'header' => 'Retry-After',
        ],
        
        'as tooManyRequestsException' => [
            'class' => 'thamtech\ratelimiter\handlers\TooManyRequestsHttpExceptionHandler',
            
            // list of rateLimits this handler should apply to
            'only' => ['ip'],
            
            // defaults to 'Rate limit exceeded.' if not set
            'message' => 'There were too many requests',
        ],
    ];
    return $behaviors;
}
```


### Storage

In order to assign one or more rate limits, you must be able to store
an integer `allowance` value and a `timestamp` for each configured
rate limit.

#### Default Allowance Storage in Cache

The RateLimiter uses an `allowanceStorage` component for storing the rate
limit values. By default, the `AllowanceCacheStorage` component stores the
allowance data in the cache component you specify. If no cache
is specified, an instance of `yii\caching\DummyCache` will be used.

You can specify a cache component in several ways:

```php
<?php
...
'allowanceStorage' => [
    // EXAMPLE `cache` definitions:
    
    // as a string referencing an application cache component
    'cache' => 'cache', // refers to the Yii::$app->cache component
    
    // as a string referencing a Cache implementation class that
    // needs no configuration
    'cache' => 'app\some\implementation\of\Cache',
    
    // as a configuration array specifying a Cache class and
    // necessary configuration settings
    'cache' => [
        'class' => 'yii\caching\DbCache',
        'cacheTable' => 'allowance_cache',
    ],
    
    // or as an already-instantiated Cache object
    'cache' => Yii::createObject([
        'class' => 'yii\caching\MemCache',
        'servers' => [
            [
                'host' => 'server1',
                'port' => 11211,
                'weight' => 60,
            ],
            [
                'host' => 'server2',
                'port' => 11211,
                'weight' => 40,
            ],
        ],
    ]),
],

```

#### Implement Your Own Storage Layer

However, you may use your own storage layer implementation by implementing
`AllowanceStorageInterface` and referencing your implementation in
`RateLimiter`'s `allowanceStorage` component.

For example:

```php
<?php
...
'components' => [
    // EXAMPLE `allowanceStorage` definitions:
    
    // as a string referencing an AllowanceStorageInterface implementation class
    // that needs no configuration
    'allowanceStorage' => 'app\components\MyAllowanceStorage',
    
    // as a configuration array specifying an AllowanceStorageInterface implementation class
    // and necessary configuration settings
    'allowanceStorage' => [
        'class' => 'app\components\MyAllowanceStorage',
        'prefix' => 'my_allowances',
        'tag' => 'my_controller_id',
    ],
],
...
```

### Defining Rate Limits

A single `RateLimiter` can have one or more separate rate limits defined. For example,
you may wish to provide one rate limit for each IP address and a different rate limit for
each authenticated user, especially when this is not always a one-to-one mapping.

A simple rate limit can be defined like the following:

```php
<?php
'components' => [
    'rateLimit' => [
        'ip' => [
            'limit' => 1000, // allowed hits per window
            'window' => 3600, // window in seconds
            
            // Callable or anonymous function returning some unique
            // identifier. A separate allowance will be tracked for
            // each identifier.
            // 
            // Leave unset to make such a rate apply globally
            // to all requests coming in through the controller
            'identifier' => function($context, $rateLimitId) {
                return $context->request->getUserIP();
            }
        ],
    ],
],
```

By returning an `identifier` value, you can enforce the defined rate limit on
a per-identifier basis, such as per IP address. Or you may leave the identifier
unspecified in order to apply the defined rate limit globally.

You can also implement your own rate limit definition by referencing
an implementation of the `RateLimitInterface`:

```php
<?php
'components' => [
    'rateLimit' => [
        'user' => [
            'class' => 'app\models\User', // implements RateLimitInterface
        ],
    ],
],
```

You will need to implement the `getRateLimit($context)` method to return
a `RateLimit` object or an array of its properties (`limit`, `window`, and optionally
an `identifier` such as a user ID, and optionally an `active` boolean).

### Defining Responses

There can be any number of responses to an exceeded rate limit. A couple of predefined
response types can be attached as behaviors of the `RateLimiter`. For example:
```php
<?php
...
'as rateLimitHeaders' => [
    'class' => 'thamtech\ratelimiter\handlers\RateLimitHeadersHandler',
],

'as retryAfterHeader' => [
    'class' => 'thamtech\ratelimiter\handlers\RetryAfterHeaderHandler',
],

'as tooManyRequestsException' => [
    'class' => 'thamtech\ratelimiter\handlers\TooManyRequestsHttpExceptionHandler',
],
...
```

A more advanced example with some additional configuration options set:

```php
<?php
...
'as rateLimitHeaders' => [
    'class' => 'thamtech\ratelimiter\handlers\RateLimitHeadersHandler',
            
    // list of rateLimits to ignore
    'except' => ['user'],
    
    // single string prefix, or an array of strings to duplicate
    // the headers with multiple prefixes.
    // Default prefix is 'X-Rate-Limit-' if this property is not specified
    'prefix' => ['X-Rate-Limit-', 'X-RateLimit-'],
],

'as retryAfterHeader' => [
    'class' => 'thamtech\ratelimiter\handlers\RetryAfterHeaderHandler',
    
    // defaults to 'Retry-After' if not set
    'header' => 'Retry-After',
],

'as tooManyRequestsException' => [
    'class' => 'thamtech\ratelimiter\handlers\TooManyRequestsHttpExceptionHandler',
    
    // list of rateLimits this handler should apply to
    'only' => ['ip'],
    
    // defaults to 'Rate limit exceeded.' if not set
    'message' => 'There were too many requests',
],
...
```

You may also attach your own event handlers to the `RateLimiter` object to look
for `RateLimiter::EVENT_RATE_LIMITS_EXCEEDED` or `RateLimiter::EVENT_RATE_LIMITS_CHECKED` events:

```php
<?php
...
'on rateLimitsExceeded' => function($event) {
    Yii::info('Rate limits exceeded: ' . $event->rateLimit);
},
...
```

Alternatively, you can attach event handlers using the `on()` method:

```php
<?php
use thamtech\ratelimiter\RateLimiter;
$rateLimiter = $controller->getBehavior('rateLimiter');
$rateLimiter->on(RateLimiter::EVENT_RATE_LIMITS_EXCEEDED, [$this, 'onRateLimitExceeded']);
```

See [Yii's Events page](http://www.yiiframework.com/doc-2.0/guide-concept-events.html) for
more information about attaching Event handlers.

##### Filtering Events

If the `only` and `except` properties of the handlers are not enough for filtering,
your custom event handler can set `$event->handled` to true to prevent any other handler
from being invoked.

For example, if you want to whitelist an IP address:

```php
<?php
...
'on rateLimitsExceeded' => function($event) {
    if ($event->context->request->getUserIp() == '127.0.0.1') {
        $event->handled = true;
    }
    // other handlers will not be invoked when IP is 127.0.0.1
}
...
```

#### Events

#### RateLimitsCheckedEvent

The `RateLimitsCheckedEvent` is triggered whenever a set of defined rate limits
are checked. The `RateLimitsExceededEvent` is triggered whenever one or more of the defined
rate limits are exceeded. The predefined responses attached to the `RateLimiter` as
behaviors all work by registering themselves as event handlers and looking for
these events.

Both events include the following properties:
* `$context` - the Context object containing the `yii\web\Request` and `$action`
* `$time` - the current unix timestamp
* `$rateLimits` - an array of `RateLimitResult` objects. Only those rate limits
  that were exceeded are included in the `RateLimitsExceededEvent`.

Predefined responses such as the `RateLimitHeadersHandler` will make use of the
rate information in order to output the appropriate values in the HTTP headers.
In the case of `RateLimitHeadersHandler`, the information from multiple rate
limits may be combined together in order to compute one set of HTTP header values.

Other responses, such as the `TooManyRequestsHttpExceptionHandler`, will not care
what or how many rate limits were exceeded. It will just throw the
`TooManyRequestsHttpException` no matter what.

Some responses are triggered whenever rate limits are checked, such as the
`RateLimitHeadersHandler` which will add its HTTP headers to every response
to indicate to the client how it is doing with respect to its rate limits.

Other responses, such as the `TooManyRequestsHttpExceptionHandler` will
only apply when rate limits are exceeded.

#### Order of Responses

The order in which responses are added matters.

Response handlers are executed in the order in which they are attached to the
`RateLimiter`. This means that an exception-throwing handler, like
`TooManyRequestsHttpException`, must come after header-setting handlers, like
`RateLimitHeadersHandler`.


Recipes
-------

See [Recipes](docs/recipes.md)

See Also
--------

* [Yii's Rate Limiter Docs](http://www.yiiframework.com/doc-2.0/guide-rest-rate-limiting.html)

* [Yii's Event Docs](http://www.yiiframework.com/doc-2.0/guide-concept-events.html)

* [Leaky Bucket Algorithm](http://en.wikipedia.org/wiki/Leaky_bucket)
