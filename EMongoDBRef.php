<?php
/**
 * This class can be used to create lightweight links between objects in
 * different collections.
 */
class EMongoDBRef {
    /**
     * Creates a new database reference
     *
     * @param string $collection - Collection name (without the database
     * name).
     * @param mixed $id - The _id field of the object to which to link.
     * @param string $database - Database name.
     *
     * @return array - Returns the reference.
     */
    public static function create(string $collection, $id, $database = null) : array
    {
        $ref = [
            '$collection' => $collection,
            '$id' => $id
        ];
        if (isset($database)) {
            $ref['$db'] = $database;
        }
        return $ref;
    }
    /**
     * Fetches the object pointed to by a reference
     *
     * @param mongodb $db - Database to use.
     * @param array $ref - Reference to fetch.
     *
     * @return MongoDB\Model\BSONDocument
     */
    public static function get(MongoDB\Database $db, array $ref) : MongoDB\Model\BSONDocument
    {
        if (!isset($ref['$id']) || !isset($ref['$collection'])) {
            return;
        }
        $ns = $ref['$collection'];
        $id = $ref['$id'];
        $refdb = null;
        if (isset($ref['$db'])) {
            $refdb = $ref['$db'];
        }
        if (!is_string($ns)) {
            throw new MongoException('EMongoDBRef::get: $ref field must be a string', 10);
        }
        if (isset($refdb)) {
            if (!is_string($refdb)) {
                throw new MongoException('EMongoDBRef::get: $db field of $ref must be a string', 11);
            }
            if ($refdb != (string)$db) {
                $db = $db->_getClient()->$refdb;
            }
        }
        $collection = $db->__get($ns);
        $query = ['_id' => $id];
        return $collection->findOne($query);
    }
    /**
     * Checks if an array is a database reference
     *
     * @param array $ref - Array to check.
     *
     * @return bool
     */
    public static function isRef(array $ref) : bool
    {
        if (isset($ref['$id']) && isset($ref['$collection'])) {
            return true;
        }
        return false;
    }
}