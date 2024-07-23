<?php

namespace UserAuth\Model\User\Storage;

use PDO;
use UserAuth\Model\User\Storage\AbstractStorage;
use UserAuth\Model\User\Storage\StorageInterface;
use UserAuth\Exception\StorageException;

class MySQLStorage extends AbstractStorage implements StorageInterface
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

        // create the query to insert ...
        $query = "INSERT INTO `".$this->getTableName()."` SET ";
        // ... using all keys in $content array
        $keys = array_keys($content);
        array_walk($keys, function (&$k) { $k = "`$k`=:".$k;});
        $query .= implode(', ', $keys);

        $prepared = $this->getDatabaseConnection()->prepare($query);
        return $prepared->execute($content);
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

        // find an key name that is not already in the content array, just for safety
        $idKeyName = 'idArg';
        $i = 0;
        while(isset($content[$idKeyName])) {
            $idKeyName = 'idArg'.($i++);
            if($i >= 100) {
                throw new StorageException('fail safe reached 100');
            }
        }

        // create the query to update ...
        $query = "UPDATE `".$this->getTableName()."` SET ";
        // ... using all keys in $content array ...
        $keys = array_keys($content);
        array_walk($keys, function (&$k) { $k = "`$k`=:".$k;});
        $query .= implode(', ', $keys);
        // ... only for the item with the correct ID
        $query .= " WHERE `".$this->getIdField()."` LIKE :$idKeyName LIMIT 1";

        $prepared = $this->getDatabaseConnection()->prepare($query);

        // Add ID to content that will be used for executing the prepared statement
        $content[$idKeyName] = $id;
        return $prepared->execute($content);
    }

    /**
     * Remove an entity
     * @param string|int $id the ID of the entity to remove
     * @return bool true on success, false otherwise
     */
    public function delete(string|int $id): bool
    {
        $query = "DELETE `".$this->getTableName()."` WHERE `".$this->getIdField()."` LIKE ? LIMIT 1";
        $prepared = $this->getDatabaseConnection()->prepare($query);
        return $prepared->execute([$id]);
    }

    /**
     * Read an entity
     * @param string|int $id the ID of the entity
     * @param mixed $fields the fields to return, can be empty to return all
     * @return array the requested data
     */
    public function read(string|int $id, null|array $fields = null): array
    {
        $query = "SELECT ";
        if(is_array($fields) && count($fields)) {
            $query .= "`".implode("`, `", $fields)."`";
        } else {
            $query .= '*';
        }
        $query .= " FROM `".$this->getTableName()."` WHERE `".$this->getIdField()."` LIKE ? LIMIT 1";
        $prepared = $this->getDatabaseConnection()->prepare($query);
        $prepared->execute([$id]);
        return $prepared->fetch(PDO::FETCH_ASSOC);
    }

    public function findByToken(string $token, null|array $fields = null): bool|array
    {
        $query = "SELECT ";
        if(is_array($fields) && count($fields)) {
            $query .= "`".implode("`, `", $fields)."`";
        } else {
            $query .= '*';
        }
        $query .= " FROM `".$this->getTableName()."` WHERE `".$this->getTokenField()."` LIKE ? LIMIT 1";
        $prepared = $this->getDatabaseConnection()->prepare($query);
        $prepared->execute([$token]);
        return $prepared->fetch(PDO::FETCH_ASSOC);
    }

    public function findUniqueValue(string $fieldName, $cbGenerate): mixed
    {
        $query = "SELECT `".$fieldName."` FROM `".$this->getTableName()."` WHERE `".$fieldName."` LIKE ? LIMIT 1";
        $prepared = $this->getDatabaseConnection()->prepare($query);
        $i = 0;
        while($i < 500) {
            $value = call_user_func($cbGenerate);
            $prepared->execute([$value]);
            if($prepared->rowCount() === 0) {
                break;
            }
            $i++;
        }
        if($i >= 500) {
            throw new StorageException('Could not create a unique token');
        }
        return $value;
    }

    private $tablename;
    /**
     * Set the table name for the entities
     * @param string $string
     * @return \UserAuth\Model\User\Storage\MySQLStorage
     */
    public function setTableName(string $string): self
    {
        $this->tablename = $string;
        return $this;
    }
    protected function getTableName(): string
    {
        if(!$this->tablename) {
            throw new StorageException('You must specify the name of the table "$storage->setTableName(\'users\');" before using this Storage');
        }
        return $this->tablename;
    }

    private $db;
    /**
     * Set the PDO connection to the MySQL storage
     * @param \PDO $obj
     * @return \UserAuth\Model\User\Storage\MySQLStorage
     */
    public function setDatabaseConnection(PDO $obj): self
    {
        $this->db = $obj;
        return $this;
    }
    protected function getDatabaseConnection(): PDO
    {
        if(! $this->db instanceof PDO) {
            throw new StorageException('DB is not a PDO Object');
        }
        return $this->db;
    }
}
