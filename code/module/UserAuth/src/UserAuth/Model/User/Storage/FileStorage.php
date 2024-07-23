<?php

namespace UserAuth\Model\User\Storage;

use UserAuth\Model\User\Storage\AbstractStorage;
use UserAuth\Model\User\Storage\StorageInterface;
use UserAuth\Exception\StorageException;

class FileStorage extends AbstractStorage implements StorageInterface
{
    /**
    * Insert a new entity
    * @param array $content the content to insert
    * @return bool, true on success, false otherwise
    */
    public function insert(array $content): bool
    {
        if(!isset($content[$this->getIdField()])) {
            throw new StorageException($this->getIdField(). " not found in content array");
        }
        foreach($this->content as $user) {
            if($user[$this->getIdField()] == $content[$this->getIdField()]) {
                throw new StorageException("Id field duplicated");
            }
        }
        $this->content[] = $content;
        return false;
    }

    /**
     * Update an existing entity
     * @param string|int $id the ID of the entity (using the setTableName())
     * @param array $content the content to update
     * @return bool true on success, false otherwise
     */
    public function update(string|int $id, array $content): bool
    {
        foreach($this->content as $key => $user) {
            if($user[$this->getIdField()] == $id) {
                $this->content[$key] = array_replace_recursive($this->content[$key], $content);
                return true;
            }
        }
        return false;
    }

    /**
     * Remove an entity
     * @param string|int $id the ID of the entity to remove
     * @return bool true on success, false otherwise
     */
    public function delete(string|int $id): bool
    {
        foreach($this->content as $key => $user) {
            if($user[$this->getIdField()] == $id) {
                unset($this->content[$key]);
                return true;
            }
        }
        return false;
    }

    /**
     * Read an entity
     * @param string|int $id the ID of the entity
     * @param mixed $fields the fields to return, can be empty to return all
     * @return array the requested data
     */
    public function read(string|int $id, null|array $fields = null): array
    {
        $foundUser = null;
        foreach($this->content as $user) {
            if($user[$this->getIdField()] == $id) {
                $foundUser = $user;
                break;
            }
        }
        if(!$foundUser) {
            return [];
        }
        if(is_array($fields) && count($fields)) {
            return array_intersect_key($foundUser, array_flip($fields));
        }
        return $foundUser;
    }

    public function findUniqueValue(string $fieldName, $cbGenerate): mixed
    {
        $i = 0;
        $value = '';
        while($i++ < 100) {
            $value = call_user_func($cbGenerate);
            $found = false;
            foreach($this->content as $user) {
                if($user[$fieldName] == $value) {
                    $found = true;
                    break;
                }
            }
            if(!$found) {
                break;
            }
        }
        if($i >= 100) {
            throw new StorageException('Could not create a unique token');
        }
        return $value;
    }

    public function findByToken(string $token, null|array $fields = null): bool|array
    {
        foreach($this->content as $user) {
            if($user[$this->getTokenField()] == $token) {
                return $user;
            }
        }
        return false;
    }

    private $filename;
    private $content;
    /**
     * Set the file name for storing the data
     * @param string $filename
     * @return \UserAuth\Model\User\Storage\MySQLStorage
     */
    public function setFilename(string $filename): self
    {
        $pathinfo = pathinfo($filename);
        $this->filename = realpath($pathinfo['dirname']);
        if($this->filename === false || strpos($this->filename, '/var/www/') !== 0) {
            throw new StorageException('Could not create file in this location. If this is not an error, you will need to overwrite the getFilename function');
        }
        $this->filename .= DIRECTORY_SEPARATOR.$pathinfo['basename'];
        if(!file_exists($this->filename) && !mkdir(dirname($this->filename), 0600, true) && !touch($this->filename)) {
            throw new StorageException('Could not create file '.$this->filename);
        }
        if(!is_writable($this->filename)) {
            throw new StorageException('File is not accessible or does not exists');
        }

        $content = file_get_contents($this->filename);
        if(!$content) {
            $content = '[]';
        }
        $this->content = json_decode($content, true);

        return $this;
    }

    /**
     * Write the file as you leave
     */
    public function __destruct()
    {
        file_put_contents($this->filename, json_encode($this->content));
    }
}
