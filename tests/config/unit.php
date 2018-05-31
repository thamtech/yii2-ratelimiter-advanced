<?php
/**
 * Application config for common unit tests
 */
return yii\helpers\ArrayHelper::merge(
    require(__DIR__ . '/config.php'),
    [
        'id' => 'app-common',
        'basePath' => dirname(__DIR__),
        'components' => [
            'cache' => 'yii\caching\DummyCache',
        ],
    ]
);
