<?php

namespace UserAuth\Model\User\Storage;

/**
 * Used to save the users. A storage could be MySQL, LDAP, Mongodb, SQLite, FileSystem, etc.
 * @author francois.dupras@hc-sc.gc.ca
 */
interface StorageInterface
{
    public function insert(array $content): bool;
    public function update(string|int $id, array $content): bool;
    public function delete(string|int $id): bool;
    public function read(string|int $id, null|array $fields = null): array;
    public function findUniqueValue(string $fieldName, $cbGenerate): mixed;
    public function findByToken(string $token, null|array $fields = null): bool|array;
    public function setIdField(string $string): self;
}
