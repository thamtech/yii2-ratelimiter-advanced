Yii2 Advanced Rate Limiter Recipes
==================================

* [Basic Limit](#basic-limit)
* [Basic Limit Per IP Address](#basic-limit-per-ip-address)
* [Basic Limit Per User ID](#basic-limit-per-user-id)
* [Different Limits for Different Identifiers](#different-limits-for-different-identifiers)
* [Different Limits for Different Controller Actions](#different-limits-for-different-controller-actions)
* [Advanced Rate Limits](#advanced-rate-limits)

Basic Limit
-----------

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
                    'basic' => [
                        // at most 100 requests within 600 seconds
                        'limit' => 100,
                        'window' => 600,
                    ],
                ],
            ],
            'allowanceStorage' => [
                'cache' => 'cache', // use Yii::$app->cache component
            ],
            
            // add X-Rate-Limit-* HTTP headers to the response
            'as rateLimitHeaders' => 'thamtech\ratelimiter\handlers\RateLimitHeadersHandler',
            
            // throw TooManyRequestsHttpException when the limit is exceeded
            'as tooManyRequestsException' => 'thamtech\ratelimiter\handlers\TooManyRequestsHttpExceptionHandler',
        ]
    ];
    
    return $behaviors;
}
```

Basic Limit Per IP Address
--------------------------

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
                    'basic' => [
                        // at most 100 requests within 600 seconds
                        'limit' => 100,
                        'window' => 600,
                        
                        // this causes a separate rate limit to be tracked for
                        // each IP address
                        'identifier' => function($context, $rateLimitId) {
                            return $context->request->getUserIP();
                        },
                    ],
                ],
            ],
            'allowanceStorage' => [
                'cache' => 'cache', // use Yii::$app->cache component
            ],
            
            // add X-Rate-Limit-* HTTP headers to the response
            'as rateLimitHeaders' => 'thamtech\ratelimiter\handlers\RateLimitHeadersHandler',
            
            // throw TooManyRequestsHttpException when the limit is exceeded
            'as tooManyRequestsException' => 'thamtech\ratelimiter\handlers\TooManyRequestsHttpExceptionHandler',
        ]
    ];
    
    return $behaviors;
}
```

Basic Limit Per User ID
-----------------------

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
                    'basic' => [
                        // at most 100 requests within 600 seconds
                        'limit' => 100,
                        'window' => 600,
                        
                        // this causes a separate rate limit to be tracked for
                        // each user ID
                        'identifier' => Yii::$app->user->getIdentity()->id,
                    ],
                ],
            ],
            'allowanceStorage' => [
                'cache' => 'cache', // use Yii::$app->cache component
            ],
            
            // add X-Rate-Limit-* HTTP headers to the response
            'as rateLimitHeaders' => 'thamtech\ratelimiter\handlers\RateLimitHeadersHandler',
            
            // throw TooManyRequestsHttpException when the limit is exceeded
            'as tooManyRequestsException' => 'thamtech\ratelimiter\handlers\TooManyRequestsHttpExceptionHandler',
        ]
    ];
    
    return $behaviors;
}
```

Different Limits for Different Identifiers
------------------------------------------

This recipe considers the case where you want to apply two or more separate
limits at the same time. In this example, we will apply a 1000 requests per
hour limit by IP address as well as a 100 requests per hour limit by user ID.

Both limits are checked and either or both may trigger the handlers.

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
                        // at most 1000 requests within 3600 seconds
                        'limit' => 1000,
                        'window' => 3600,
                        
                        // this causes a separate rate limit to be tracked for
                        // each IP address
                        'identifier' => function($context, $rateLimitId) {
                            return $context->request->getUserIP();
                        },
                    ],
                    
                    'user' => [
                        // at most 100 requests within 3600 seconds
                        'limit' => 100,
                        'window' => 3600,
                        
                        // this causes a separate rate limit to be tracked for
                        // each user ID
                        'identifier' => Yii::$app->user->getIdentity()->id,
                    ],
                ],
            ],
            'allowanceStorage' => [
                'cache' => 'cache', // use Yii::$app->cache component
            ],
            
            // add X-Rate-Limit-* HTTP headers to the response
            'as rateLimitHeaders' => 'thamtech\ratelimiter\handlers\RateLimitHeadersHandler',
            
            // throw TooManyRequestsHttpException when the limit is exceeded
            'as tooManyRequestsException' => 'thamtech\ratelimiter\handlers\TooManyRequestsHttpExceptionHandler',
        ]
    ];
    
    return $behaviors;
}
```

Different Limits for Different Controller Actions
-------------------------------------------------

This recipe demonstrates how to configure one set of limits on the `index` and
`view` actions and another set of limits on the remaining actions (such as
`update` and `delete`).

```php
<?php
public function behaviors()
{
    $behaviors = parent::behaviors();
    $behaviors['rateLimiterRead'] = [
        'class' => 'thamtech\ratelimiter\RateLimiter',
        
        // this rate limit filter only applies to index and view actions
        'only' => ['index', 'view'],
        
        'components' => [
            'rateLimit' => [
                'definitions' => [
                    'basic' => [
                        // at most 1000 requests within 600 seconds
                        'limit' => 1000,
                        'window' => 600,
                    ],
                ],
            ],
            'allowanceStorage' => [
                'cache' => 'cache', // use Yii::$app->cache component
            ],
            
            // add X-Rate-Limit-* HTTP headers to the response
            'as rateLimitHeaders' => 'thamtech\ratelimiter\handlers\RateLimitHeadersHandler',
            
            // throw TooManyRequestsHttpException when the limit is exceeded
            'as tooManyRequestsException' => 'thamtech\ratelimiter\handlers\TooManyRequestsHttpExceptionHandler',
        ]
    ];
    
    $behaviors['rateLimiterWrite'] = [
        'class' => 'thamtech\ratelimiter\RateLimiter',
        
        // this rate limit filter applies to all actions except index and view
        'except' => ['index', 'view'],
        
        'components' => [
            'rateLimit' => [
                'definitions' => [
                    'basic' => [
                        // at most 40 requests within 1000 seconds
                        'limit' => 40,
                        'window' => 1000,
                    ],
                ],
            ],
            'allowanceStorage' => [
                'cache' => 'cache', // use Yii::$app->cache component
            ],
            
            // add X-Rate-Limit-* HTTP headers to the response
            'as rateLimitHeaders' => 'thamtech\ratelimiter\handlers\RateLimitHeadersHandler',
            
            // throw TooManyRequestsHttpException when the limit is exceeded
            'as tooManyRequestsException' => 'thamtech\ratelimiter\handlers\TooManyRequestsHttpExceptionHandler',
        ]
    ];
    
    return $behaviors;
}
```

Advanced Rate Limits
--------------------

This recipe demonstrates an advanced rate limit configuration.

1. Different rate limits for different actions
2. Separate rate limits by IP address and user ID
3. HTTP Header responses
   * `429 Too Many Requests` response - [RFC 6585, section 4](http://tools.ietf.org/html/6585#section-4)
   * `Retry-After` header - [RFC 7231, section 7.1.3](https://tools.ietf.org/html/rfc7231#section-7.1.3)
   * RateLimit headers - implied by [RFC 6585, section 4](http://tools.ietf.org/html/6585#section-4)
4. Custom action on checks
5. Custom action on limits exceeded

```php
<?php
public function behaviors()
{
    $behaviors = parent::behaviors();
    $behaviors['rateLimiterRead'] = [
        'class' => 'thamtech\ratelimiter\RateLimiter',
        
        // this rate limit filter only applies to index and view actions
        'only' => ['index', 'view'],
        
        'components' => [
            'rateLimit' => [
                'definitions' => [
                    'ip' => [
                        // at most 1000 requests within 3600 seconds
                        'limit' => 1000,
                        'window' => 3600,
                        
                        // this causes a separate rate limit to be tracked for
                        // each IP address
                        'identifier' => function($context, $rateLimitId) {
                            return $context->request->getUserIP();
                        },
                    ],
                    
                    'user' => [
                        // at most 100 requests within 3600 seconds
                        'limit' => 100,
                        'window' => 3600,
                        
                        // this causes a separate rate limit to be tracked for
                        // each user ID
                        'identifier' => Yii::$app->user->getIdentity()->id,
                    ],
                ],
            ],
            'allowanceStorage' => [
                'cache' => 'cache', // use Yii::$app->cache component
            ],
            
            // add X-RateLimit-* HTTP headers to the response
            'as rateLimitHeaders' => [
                'class' => 'thamtech\ratelimiter\handlers\RateLimitHeadersHandler',
                
                'prefix' => 'X-RateLimit-', // use the X-RateLimit- prefix instead of default X-Rate-Limit
            ],
            
            // add Retry-After HTTP header to the response
            'as retryAfterHeader' => 'thamtech\ratelimiter\handlers\RetryAfterHeaderHandler',
            
            // throw TooManyRequestsHttpException when the limit is exceeded
            'as tooManyRequestsExceptionIp' => [
                'class' => 'thamtech\ratelimiter\handlers\TooManyRequestsHttpExceptionHandler',
                
                'only' => ['ip'],
                
                // custom message instead of default 'Rate limit exceeded.'
                'message' => 'There were too many requests from your IP address.',
            ],
            
            // throw TooManyRequestsHttpException when the limit is exceeded
            'as tooManyRequestsExceptionUser' => [
                'class' => 'thamtech\ratelimiter\handlers\TooManyRequestsHttpExceptionHandler',
                
                'only' => ['user'],
                
                // custom message instead of default 'Rate limit exceeded.'
                'message' => 'There were too many requests from your user account.',
            ],
            
            // custom action on check
            'on rateLimitsChecked' => function($event) {
                Yii::info('Rate limits checked: ' . $event->rateLimit);
            },
            
            // custom action on limits exceeded
            'on rateLimitsExceeded' => function($event) {
                Yii::info('Rate limits exceeded: ' . $event->rateLimit);
            },
        ]
    ];
    
    $behaviors['rateLimiterWrite'] = [
        'class' => 'thamtech\ratelimiter\RateLimiter',
        
        // this rate limit filter applies to all actions except index and view
        'except' => ['index', 'view'],
        
        'components' => [
            'rateLimit' => [
                'definitions' => [
                    'basic' => [
                        // at most 40 requests within 1000 seconds
                        'limit' => 40,
                        'window' => 1000,
                    ],
                ],
            ],
            'allowanceStorage' => [
                'cache' => 'cache', // use Yii::$app->cache component
            ],
            
            // add X-Rate-Limit-* HTTP headers to the response
            'as rateLimitHeaders' => 'thamtech\ratelimiter\handlers\RateLimitHeadersHandler',
            
            // throw TooManyRequestsHttpException when the limit is exceeded
            'as tooManyRequestsException' => 'thamtech\ratelimiter\handlers\TooManyRequestsHttpExceptionHandler',
        ]
    ];
    
    return $behaviors;
}
```
