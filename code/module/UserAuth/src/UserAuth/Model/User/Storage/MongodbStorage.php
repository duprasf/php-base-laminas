<?php

namespace UserAuth\Model\User\Storage;

use MongoDB\Database as MongoDb;
use MongoDB\Client as MongoClient;
use MongoDB\Collection as MongoCollection;
use UserAuth\Model\User\Storage\AbstractStorage;
use UserAuth\Exception\StorageException;
use UserAuth\Model\User\Storage\StorageInterface;

class MongodbStorage extends AbstractStorage implements StorageInterface
{
    /**
    * Insert a new entity
    * @param array $content the content to insert
    * @return bool, true on success, false otherwise
    */
    public function insert(array $content): bool
    {
        if(!count($content)) {
            return false;
        }
        return $this->getDatabaseConnection()
            ->insertOne($content)
            ->getInsertedCount() === 1
        ;
    }

    /**
     * Update an existing entity
     * @param string|int $id the ID of the entity (using the setTableName())
     * @param array $content the content to update
     * @throws \UserAuth\Exception\StorageException
     * @return bool true on success, false otherwise
     */
    public function update(string|int $id, array $content): bool
    {
        if(!count($content)) {
            return false;
        }
        return $this
            ->getDatabaseConnection()
            ->updateOne([$this->getIdField() => $id], ['$set' => $content])
            ->getMatchedCount() === 1
        ;
    }

    /**
     * Remove an entity
     * @param string|int $id the ID of the entity to remove
     * @return bool true on success, false otherwise
     */
    public function delete(string|int $id): bool
    {
        return $this
            ->getDatabaseConnection()
            ->deleteOne([$this->getIdField() => $id])
            ->getDeletedCount() === 1
        ;
    }

    /**
     * Read an entity
     * @param string|int $id the ID of the entity
     * @param mixed $fields the fields to return, can be empty to return all
     * @return bool|array the requested data or false if not found
     */
    public function read(string|int $id, null|array $fields = null): bool|array
    {
        if(is_array($fields) && isset($fields[0])) {
            // if keys are numeric, we need to set values as keys to return fields
            $fields = array_fill_keys($fields, 1);
        }
        $fields['typeMap'] = ['root' => 'array', 'document' => 'array', 'array' => 'array'];
        $db = $this->getDatabaseConnection();
        return $db->findOne([$this->getIdField() => $id], $fields ?? []);
    }

    public function findByToken(string $token, null|array $fields = null): bool|array
    {
        if(isset($fields[0])) {
            $fields = array_fill_keys($fields, 1);
        }
        $fields['typeMap'] = ['root' => 'array', 'document' => 'array', 'array' => 'array'];
        $db = $this->getDatabaseConnection();
        return $db->findOne([$this->getTokenField() => $token], $fields ?? []) ?? false;
    }

    public function findUniqueValue(string $fieldName, $cbGenerate): mixed
    {
        $db = $this->getDatabaseConnection();
        $i = 0;
        while($i < 500) {
            $value = call_user_func($cbGenerate);
            if($db->countDocuments([$fieldName => $value]) === 0) {
                break;
            }
            $i++;
        }
        if($i >= 500) {
            throw new StorageException('Could not create a unique token');
        }
        return $value;
    }

    private $collection;
    /**
     * Set the collection name for the entities
     * @param string $string
     * @return \UserAuth\Model\User\Storage\MongodbStorage
     */
    public function setCollectionName(string $string): self
    {
        $this->collection = $string;
        return $this;
    }
    protected function getCollectionName(): string
    {
        return $this->collection;
    }

    private $db;
    /**
     * Set the MongoDb connection to the MySQL storage
     * @param \MongoDb $obj
     * @return \UserAuth\Model\User\Storage\MongodbStorage
     */
    public function setDatabaseConnection(MongoDb|MongoClient|MongoCollection $obj): self
    {
        $this->db = $obj;
        return $this;
    }
    protected function getDatabaseConnection(): MongoCollection
    {
        if($this->db instanceof MongoCollection) {
            return $this->db;
        }
        if(! ($this->db instanceof MongoDb || $this->db instanceof MongoClient)) {
            throw new StorageException('DB is not a MongoDB\Database, Client or Collection object');
        }
        $collection = $this->collection;
        if(!$collection) {
            throw new StorageException("A collection name is required");
        }
        $this->db = $this->db->$collection;
        return $this->db;
    }
}
