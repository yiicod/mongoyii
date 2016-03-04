<?php

class Interest extends EMongoDocument
{
	public $name;
	public $i_id;
	public function rules()
	{
		return [
			['i_id, otherId, username', 'safe'],
		];
	}

	public function collectionName()
	{
		return 'interests';
	}

	/**
	 * Returns the static model of the specified AR class.
	 * @return User the static model class
	 */
	public static function model($className = __CLASS__)
	{
		return parent::model($className);
	}
}