<?php

namespace UserAuth\Model\User\Storage;

use ActiveDirectory\Model\ActiveDirectory;
use UserAuth\Model\User\Storage\AbstractStorage;
use UserAuth\Model\User\Storage\StorageInterface;
use UserAuth\Exception\StorageException;

class LdapStorage extends AbstractStorage implements StorageInterface
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
        throw new StorageException('Inserting in AD is not supported at this time.');
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
        throw new StorageException('Updating in AD is not supported at this time.');
    }

    /**
     * Remove an entity
     * @param string|int $id the ID of the entity to remove
     * @return bool true on success, false otherwise
     */
    public function delete(string|int $id): bool
    {
        throw new StorageException('Deleting in AD is not supported at this time.');
    }

    /**
     * Read an entity
     * @param string|int $id the ID of the entity
     * @param mixed $fields the fields to return, can be empty to return all
     * @return array the requested data
     */
    public function read(string|int $id, null|array $fields = null): array
    {
        $data = $this->getActiveDirectoryConnection()->getUserByEmailOrUsername($id, returnFirstElementOnly: true);
        if(!$data) {
            throw new StorageException('Could not find user');
        }
        if(is_array($fields) && count($fields)) {
            return array_intersect_key($data, array_flip($fields));
        }
        unset($data['raw']);
        return $data;
    }

    public function validateCredentials(string $username, $password): bool
    {
        return $this->getActiveDirectoryConnection()->validateCredentials($username, $password);
    }

    public function findByToken(string $token, null|array $fields = null): bool|array
    {
        throw new StorageException('findByToken in AD is not supported at this time.');
    }

    public function findUniqueValue(string $fieldName, $cbGenerate): mixed
    {
        throw new StorageException('findUniqueValue in AD is not supported at this time.');
    }

    private $ldap;
    /**
     * Set the LDAP connection to the AD
     * @param \ActiveDirectory\Model\ActiveDirectory $obj
     * @return \UserAuth\Model\User\Storage\LdapStorage
     */
    public function setActiveDirectoryConnection(ActiveDirectory $obj): self
    {
        $this->ldap = $obj;
        return $this;
    }
    protected function getActiveDirectoryConnection(): ActiveDirectory
    {
        if(! $this->ldap instanceof ActiveDirectory) {
            throw new StorageException('Active Directory connect was not provided');
        }
        return $this->ldap;
    }
}
