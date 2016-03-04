<?php

/**
 * EMongoMigration is designed to be used together with the "yiic migratemongo" command.
 *
 * It provides a set of convenient methods for manipulating database data.
 * For example, the {@link insert} method can be used to easily insert data into
 * a mongo collection; the {@link createCollection} method can be used to create a new collection.
 * Compared with the same methods in {@link MongoDB\Collection}, these methods will display extra
 * information showing the method parameters and execution time, which may be useful when
 * applying migrations.
 */
abstract class EMongoMigration extends CComponent
{

    /**
     *
     * @var string global collection name to be used for every mongodb operation 
     * Can also be set dynamically
     * @see setCollectionName
     * @see getCollectionName
     */
    protected $collectionName;

    /**
     * The name of the database 
     * Used by set/getDbConnection()
     * @var EMongoClient
     */
    private $_db;

    /**
     * Creates a collection.
     * @param string $name The name of the collection.
     * @param array $options Options (name=>value) for the operation. See {@link MongoDB::createCollection} for more details.
     */
    public function createCollection($name, $options = [])
    {
        echo "    > creating collection $name ...";
        $time = microtime(true);

        $this->getDbConnection()->getDB()->createCollection($name, $options);

        echo " done (time: " . sprintf('%.3f', microtime(true) - $time) . "s)\n";
    }

    /**
     * Deletes an index from this collection.
     * 
     * @param array $keys Deletes one or multiple indices from name
     */
    public function dropIndex($keys)
    {
        echo "    > deleteIndex ...";
        $time = microtime(true);
        $this->getDbConnection()->{$this->getCollectionName()}->dropIndex($keys);
        echo " done (time: " . sprintf('%.3f', microtime(true) - $time) . "s)\n";
    }

    /**
     * Deletes all indexes from current collection.
     */
    public function dropIndexes()
    {
        echo "    > deleteIndexes ...";
        $time = microtime(true);
        $this->getDbConnection()->{$this->getCollectionName()}->dropIndexes();
        echo " done (time: " . sprintf('%.3f', microtime(true) - $time) . "s)\n";
    }

    /**
     * This method contains the logic to be executed when removing this migration.
     * Child classes may override this method if the corresponding migrations can be removed.
     * @return boolean Returning false means, the migration will not be applied.
     */
    public function down()
    {
        
    }

    /**
     * Drops the current collection.
     */
    public function drop()
    {
        echo "    > drop collection {$this->getCollectionName()} ...";
        $time = microtime(true);
        $this->getDbConnection()->{$this->getCollectionName()}->drop();
        echo " done (time: " . sprintf('%.3f', microtime(true) - $time) . "s)\n";
    }

    /**
     * Drops the current collection.
     */
    public function dropCollection()
    {
        echo "    > dropping collection {$this->getCollectionName()}...";
        $time = microtime(true);
        $this->getDbConnection()->{$this->getCollectionName()}->drop();
        echo " done (time: " . sprintf('%.3f', microtime(true) - $time) . "s)\n";
    }

    private function indexList($array)
    {
        $ret = '';
        foreach ($array as $key => $order) {
            $ret .= $key . ' (' . ($order == - 1 ? 'desc' : 'asc') . '), ';
        }
        return substr($ret, 0, strlen($ret) - 2);
    }

    /**
     * Creates an index on the given field(s), or does nothing if the index already exists.
     * 
     * @param mixed $keys
     * An array of fields by which to sort the index on. Each element in the array has as key the field name, 
     * and as value either 1 for ascending sort, or -1 for descending sort.
     * @param array $options
     * Options (name=>value) for the save operation. See {@link MongoDB\Collection::ensureIndex} for more details.
     */
    public function createIndex($keys, $options = [])
    {
        echo "    > creating index for fields " . $this->indexList($keys) . " ...";
        $time = microtime(true);
        $this->getDbConnection()->{$this->getCollectionName()}->createIndex($keys, $options);
        echo " done (time: " . sprintf('%.3f', microtime(true) - $time) . "s)\n";
    }

    /**
     * Runs JavaScript code on the database server.
     * 
     * @param mixed $code @link MongoCode or string to execute.
     * @param array $args Arguments to be passed to code.
     */
    public function execute($code, $args = [])
    {
        echo "    > execute command: $code ...";
        $time = microtime(true);
        $this->getDbConnection()->getDB()->execute($code, $args);
        echo " done (time: " . sprintf('%.3f', microtime(true) - $time) . "s)\n";
    }

    /**
     *
     * @return string The current selected collection
     */
    public function getCollectionName()
    {
        if ($this->collectionName === null) {
            throw new CException(
            Yii::t(
                    'yii', 'You need to set a collection first in order to execute mongodb operations'
            )
            );
        }
        return $this->collectionName;
    }

    /**
     *
     * @param string $collectionName Name of the connection on which mongodb operations are applied to
     */
    public function setCollectionName($collectionName)
    {
        echo "    > switching to collecton {$collectionName}.\n";
        $this->collectionName = $collectionName;
    }

    /**
     * Returns the currently active database connection.
     * By default, the 'db' application component will be returned and activated.
     * You can call {@link setDbConnection} to switch to a different database connection.
     * Methods such as {@link insert}, {@link createCollection} will use this database connection
     * to perform DB queries.
     * 
     * @throws CException if "db" application component is not configured
     * @return EMongoClient the currently active mongodb connection
     */
    public function getDbConnection()
    {
        if ($this->_db === null) {
            $this->_db = Yii::app()->getComponent('mongodb');
            if (!$this->_db instanceof EMongoClient) {
                throw new CException(Yii::t('yii', 'The "db" application component must be configured to be a EMongoClient object.'));
            }
        }
        return $this->_db;
    }

    /**
     * Inserts a document into current collection.
     * 
     * @param array $a An array.
     * @param array $options Options (name=>value) for the insert operation. See {@link MongoDB\Collection::insert} for more details.
     */
    public function insert($a, $options = [])
    {
        echo "    > insert document ...";
        $time = microtime(true);
        $this->getDbConnection()->{$this->getCollectionName()}->insertOne($a, $options);
        echo " done (time: " . sprintf('%.3f', microtime(true) - $time) . "s)\n";
    }

    /**
     * Deletes a document from current collection.
     * 
     * @param array $criteria Description of records to remove.
     * @param array $options Options (name=>value) for the remove operation. See {@link MongoDB\Collection::remove} for more details.
     */
    public function delete($criteria, $options = [])
    {
        echo "    > remove document ...";
        $time = microtime(true);
        $this->getDbConnection()->{$this->getCollectionName()}->deleteMany($criteria, $options);
        echo " done (time: " . sprintf('%.3f', microtime(true) - $time) . "s)\n";
    }
    
    /**
     * Saves a document into current collection.
     * 
     * @param array $a Array to save. 
     * @param array $options Options (name=>value) for the save operation. See {@link MongoDB\Collection::save} for more details.
     */
    public function insertMany($a, $options = [])
    {
        echo "    > save document ...";
        $time = microtime(true);
        $this->getDbConnection()->{$this->getCollectionName()}->insertMany($a, $options);
        echo " done (time: " . sprintf('%.3f', microtime(true) - $time) . "s)\n";
    }

    /**
     * Sets the currently active database connection.
     * The database connection will be used by the methods such as {@link insert}, {@link createCollection}.
     * 
     * @param EMongoClient $db the database connection component
     */
    public function setDbConnection($db)
    {
        $this->_db = $db;
    }

    /**
     * This method contains the logic to be executed when applying this migration.
     * Child classes may implement this method to provide actual migration logic.
     * 
     * @return boolean Returning false means, the migration will not be applied.
     */
    public function up()
    {
        
    }

    /**
     * Updates a document within the current collection.
     * 
     * @param array $criteria Description of the objects to update.
     * @param array $options Options (name=>value) for the update operation. See {@link MongoDB\Collection::update} for more details.
     */
    public function update($criteria, $new_object, $options = [])
    {
        echo "    > update document ...";
        $time = microtime(true);
        $this->getDbConnection()->{$this->getCollectionName()}->updateOne($criteria, $new_object, $options);
        echo " done (time: " . sprintf('%.3f', microtime(true) - $time) . "s)\n";
    }

}
