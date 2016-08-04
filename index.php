<?php

// comment out the following two lines when deployed to production
//defined('YII_DEBUG') or define('YII_DEBUG', true);
//defined('YII_ENV') or define('YII_ENV', 'dev');

require(__DIR__ . '/vendor/autoload.php');
require(__DIR__ . '/vendor/yiisoft/yii2/Yii.php');

$config = [
    'id' => 'migration-generator',
    'basePath' => dirname(__DIR__),
    /*'aliases' => [
        '@ddanielroche/migration/Generator' => 'Generator.php',
    ],*/
    'bootstrap' => ['gii'],
    'modules' => [
        'gii' => [
            'class' => 'yii\gii\Module',
            /*'generators' => [
                'migration' => [
                    'class' => 'Generator',
                ],
            ],*/
        ],
    ],
    'components' => [
        'urlManager' => [
            'enablePrettyUrl' => true,
        ],
    ]
];

(new yii\web\Application($config))->run();
