<?php

require_once 'bootstrap.php';

class MongoClientTest extends PHPUnit_Framework_TestCase
{

    /**
     * @covers EMongoClient::getConnection
     */
    public function testSettingUpConnection()
    {
        $mongo = Yii::app()->mongodb;
        $this->assertInstanceOf('EMongoClient', $mongo);
        $this->assertInstanceOf('MongoDB\Client', $mongo->getConnection());
    }

    /**
     * @covers EMongoClient::selectCollection
     */
    public function testSelectCollection()
    {
        $mongo = Yii::app()->mongodb;

        $this->assertTrue($mongo->new_collection instanceof MongoDB\Collection);
        $this->assertInstanceOf('MongoDB\Collection', $mongo->new_collection);
        $this->assertInstanceOf('MongoDB\Collection', $mongo->selectCollection('new_collection'));
    }

    /**
     * @covers EMongoClient::getDB
     */
    public function testGetDB()
    {
        $mongo = Yii::app()->mongodb;        
        $this->assertInstanceOf('MongoDB\Database', $mongo->getDB());
    }

    /**
     * @covers EMongoClient::getDefaultWriteConcern
     */
    public function testWriteConcern()
    {
        $mongo = Yii::app()->mongodb;
        $w = $mongo->getDefaultWriteConcern();

        if (version_compare(phpversion('mongo'), '1.3.0', '<')) {
            $this->assertTrue(isset($w['safe']));
        } else {
            $this->assertTrue(isset($w['w'], $w['j']));
        }

        $mongo->w = 1;
        $mongo->j = true;

        $w = null;
        $w = $mongo->getDefaultWriteConcern();

        if (version_compare(phpversion('mongo'), '1.3.0', '<')) {
            $this->assertTrue($w['safe'] === true);
        } else {
            $this->assertTrue($w['w'] == 1 && $w['j'] === true);
        }
    }

    public function testArrayMerging()
    {
        $a = CMap::mergeArray(['a' => 1, 'b' => ['c' => 2]], ['a' => 1, 'b' => ['c' => 2, 'd' => 3]]);
        $this->assertTrue(isset($a['a'], $a['b'], $a['b']['c'], $a['b']['d']));
    }

}
