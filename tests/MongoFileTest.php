<?php

require_once 'bootstrap.php';

class MongofileTest extends PHPUnit_Framework_TestCase
{
	public function tearDown()
	{
		Yii::app()->mongodb->drop();
		parent::tearDown();
	}
	
	public function testAddingFile()
	{
		// Hmm this is blank until I can figure out how best to unit test an upload
	}
	
	public function testFindingFile()
	{
	}
	
	public function testDeletingFile()
	{
	}
}