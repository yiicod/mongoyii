<?php

define('YII_PATH', realpath(__DIR__ . '/../../../yiisoft/yii/framework'));

// disable Yii error handling logic
defined('YII_ENABLE_EXCEPTION_HANDLER') or define('YII_ENABLE_EXCEPTION_HANDLER', false);
defined('YII_ENABLE_ERROR_HANDLER') or define('YII_ENABLE_ERROR_HANDLER', false);

define('APP_ROOT', realpath(__DIR__ . '/../../../../app/protected'));

define('APP_RUNTIME', APP_ROOT . '/runtime');
define('APP_ASSETS', APP_ROOT . '/assets');

// composer autoloader
require_once(__DIR__ . '/../../../autoload.php');

require_once(YII_PATH . '/yii.php');

$env = @getenv('YII_MONGOYII_ENV');
if($env){
    $env = unserialize($env);
}else{
    $env = [];
}
Yii::createConsoleApplication(
    CMap::mergeArray([
        'name' => 'test',
        'basePath' => APP_ROOT,
        'runtimePath' => APP_RUNTIME,
        'aliases' => [
            'mongoyii' => realpath(__DIR__ . '/../'),
        ],
        'import' => [
            'mongoyii.*',
            'mongoyii.behaviors.*',
            'mongoyii.util.*',
            'mongoyii.validators.*',
        ],
        'components' => [
            // 'assetManager' => array(
            // 	'basePath' => APP_ASSETS // do not forget to clean this folder sometimes
            // ),
            'mongodb' => [
                'class' => 'mongoyii.EMongoClient',
                'db' => 'mongoyii-fake',
                'server' => 'mongodb://10.8.4.114:27017'
            ],
            'authManager' => [
                'class' => 'mongoyii.util.EMongoAuthManager'
            ]
        ]
    ], $env)
);

// See the `Boostrap.init()` method for explanation why it is needed
define('IS_IN_TESTS', true);

require_once 'models/User.php';
require_once 'models/UserTsTest.php';
require_once 'models/Interest.php';
require_once 'models/Dummy.php';
require_once 'models/Skill.php';
require_once 'models/versionedDocument.php';