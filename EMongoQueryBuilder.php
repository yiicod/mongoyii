<?php

/**
 * EMongoQueryBuilder
 *
 * Represents the Yii edition to the MongoCursor and allows for lazy loading of objects.
 *
 * This class does not support eager loading by default, in order to use eager loading you should look into using this
 * classes reponse with iterator_to_array().
 *
 * I did try originally to make this into a active data provider and use this for two fold operations but the cactivedataprovider would extend
 * a lot for the cursor and the two took quite different constructors.
 */
class EMongoQueryBuilder
{
    /**
     * @var EMongoDocument
     */
    public $model;

    /**
     * @var array|EMongoCriteria|MongoCursor|string
     */
    private $query = '';

    /**
     * @var array
     */
    private $options = [];

    /**
     * The cursor constructor
     * @param string|EMongoDocument $modelClass - The class name for the active record
     * @param array|MongoCursor|EMongoCriteria $criteria -  Either a condition array (without sort,limit and skip) or a MongoCursor Object
     * @param array $fields
     */
    public function __construct($model, $criteria = [], $fields = [])
    {

        if (is_string($model)) {
            $this->model = EMongoDocument::model($this->modelClass);
        } elseif ($model instanceof EMongoDocument) {
            $this->model = $model;
        }
        $this->options = [];

        if ($criteria instanceof EMongoCriteria) {
            $this->options['projection'] = $criteria->project;
            if ($criteria->skip > 0) {
                $this->options['skip'] = $criteria->skip;
            }
            if ($criteria->limit > 0) {
                $this->options['limit'] = intval($criteria->limit);
            }
            if ($criteria->sort) {
                $this->options['sort'] = $criteria->sort;
            }
            $this->query = $criteria->condition;
        } else {
            // Then we are doing an active query
            $this->options['projection'] = $fields;
            $this->query = $criteria;
        }
    }

    /**
     * Execute query
     * @param bool $ac If set true will be returned array of EMongoDocuments if not hen array
     * of CBSONDocument
     * @return array
     */
    public function queryAll($ac = false) : array
    {
        $finded = $this->model->getCollection()->find($this->query, $this->options);
        $finded = iterator_to_array($finded);
        if ($ac === true) {
            return $this->model->populateRecords($finded, true, null, false);
        }
        return $finded;
    }

    /**
     * Counts the records returned by the criteria. By default this will not take skip and limit into account
     * you can add inject true as the first and only parameter to enable MongoDB to take those offsets into
     * consideration.
     *
     * @param bool $takeSkip
     * @return int
     */
    public function count($takeSkip = false /* Was true originally but it was to change the way the driver worked which seemed wrong */)
    {
        return $this->model->getCollection()->count($this->query, $this->options);
    }

    /**
     * Set sort fields
     * @param array $fields
     * @return EMongoQueryBuilder
     */
    public function sort(array $fields)
    {
        $this->options['sort'] = $fields;
        return $this;
    }

    /**
     * Set skip
     * @param int $num
     * @return EMongoQueryBuilder
     */
    public function skip($num = 0)
    {
        $this->options['skip'] = $num;
        return $this;
    }

    /**
     * Set limit
     * @param int $num
     * @return EMongoQueryBuilder
     */
    public function limit($num = 0)
    {
        $this->options['limit'] = intval($num);
        return $this;
    }

}