<?php

require_once 'bootstrap.php';

class MongoActiveDataProviderTest extends PHPUnit_Framework_TestCase
{
	public function tearDown()
	{
		// drop the database after every test
		Yii::app()->mongodb->users->drop();
	}

	/**
	 * I am only testing my public API, not that of the CActiveDataProvider in general
	 * @covers EMongoDataProvider
	 */
	public function testFetchData()
	{
		// drop the database before every test
		Yii::app()->mongodb->users->drop();

		for($i=0;$i<=4;$i++){
			$u = new User();
			$u->username = 'sammaye';
			$u->save();
		}

		$d = new EMongoDataProvider('User', [
			'criteria' => [
				'condition' => ['username' => 'sammaye'],
				'sort' => ['username' => -1],
			]
		]);

		$this->assertTrue($d->getTotalItemCount() == 5);
		$data = $d->fetchData();
		$this->assertTrue($d->getTotalItemCount() == 5);

		// default page size is ten which means the skip and limit become useless atm
		// However that does not matter because there is only 5 there lol
		$this->assertTrue(count($data) == 5);
//		$this->assertContainsOnlyInstancesOf('User', $data);
	}
}