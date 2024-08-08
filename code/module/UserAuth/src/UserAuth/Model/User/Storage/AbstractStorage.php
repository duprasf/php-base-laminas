<?php

namespace UserAuth\Model\User\Storage;

use UserAuth\Exception\StorageException;
use UserAuth\Model\User\Storage\StorageInterface;

abstract class AbstractStorage implements StorageInterface
{
    private $idField;
    /**
     * Set the name of the ID field for your entities (ex: email, userId, etc.)
     * @param string $string
     * @return \UserAuth\Model\User\Storage\AbstractStorage
     */
    public function setIdField(string $string): self
    {
        $this->idField = $string;
        return $this;
    }
    protected function getIdField(): string
    {
        if(!$this->idField) {
            throw new StorageException("No id field specified");
        }
        return $this->idField;
    }

    private $tokenField='token';
    /**
     * Set the name of the ID field for your entities (ex: email, userId, etc.)
     * @param string $string
     * @return \UserAuth\Model\User\Storage\AbstractStorage
     */
    public function setTokenField(string $string): self
    {
        $this->tokenField = $string;
        return $this;
    }
    protected function getTokenField(): string
    {
        return $this->tokenField;
    }
}
