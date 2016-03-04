<?php

require_once 'bootstrap.php';

class MongoLogRouteTest extends PHPUnit_Framework_TestCase
{

    public function testLogRouteGetCollection()
    {
        $router = new EMongoLogRoute();
        $router->connectionId = 'mongodb_neu';
        $this->assertEquals('mongodb_neu', $router->connectionId);

        $router->logCollectionName = 'yii_mongo_log';
        $this->assertEquals('yii_mongo_log', $router->logCollectionName);

        // set back again
        $router->connectionId = 'mongodb';

        $collection = $router->getMongoConnection();
        
        $this->assertInstanceOf('MongoDB\Collection', $collection);
    }

    public function testInsertIntoLog()
    {
        $router = new EMongoLogRoute();
        $logs = [
            ['message1', 'level1', 'category1', microtime(true)],
            ['message2', 'level2', 'category2', microtime(true)],
            ['message3', 'level3', 'category3', microtime(true)],
        ];

        $router->processLogs($logs);
        $collection = $router->getMongoConnection();

        foreach ($logs as $log) {
            $this->assertNull($collection->findOne(['message' => 'IAmNotThere']));
            $this->assertArrayHasKey('message', $collection->findOne(['message' => $log[0]]));
            $this->assertArrayHasKey('level', $collection->findOne(['level' => $log[1]]));
            $this->assertArrayHasKey('category', $collection->findOne(['category' => $log[2]]));
        }
    }

    public function tearDown()
    {
        Yii::app()->mongodb->drop();
        parent::tearDown();
    }

}
