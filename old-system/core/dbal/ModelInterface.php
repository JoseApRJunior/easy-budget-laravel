<?php

namespace core\dbal;

interface ModelInterface
{
    /**
     * Create a new entity.
     *
     * @param Entity $entity The entity to be created.
     * @return array <string,mixed > The processed result of the database query.
     */
    public function create(Entity $entity): array;

    /**
     * Update an existing entity.
     *
     * @param Entity $entity The entity to be updated.
     * @return array  <string,mixed > The processed result of the database query.
     */
    public function update(Entity $entity): array;

    /**
     * Delete an entity by its ID and tenant ID.
     *
     * @param int $id The ID of the entity to delete.
     * @param int $tenant_id The tenant ID associated with the entity.
     * @return array  <string,mixed > The processed result of the database query.
     */
    public function delete(int $id, int $tenant_id): array;

    /**
     * Find all entities.
     *
     * @return array <string,mixed >|Entity|EntityNotFound The processed result of the database query.
     */
    public function findAll(): array|Entity;

    /**
     * Find entities based on the provided criteria, with optional sorting, limiting, and field selection.
     *
     * @param array <string,mixed > $criteria An associative array of column names and their corresponding values to filter the results.
     * @param ?array <string,mixed >   $orderBy An optional associative array of column names and their corresponding sort direction ('asc' or 'desc') to order the results.
     * @param ?int $limit An optional maximum number of results to return.
     * @param ?int $offset An optional number of results to skip before returning the remaining results.
     * @param array <string> $fields An optional array of column names to select, defaults to all columns ('*').
     * @return array <string,mixed > |Entity The processed result of the database query.
     */
    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null, array $fields = [ '*' ]): array|Entity;

    /**
     * Manipula o resultado de uma consulta do banco de dados
     *
     * @param array<int, array<string, mixed>> $result Resultado da consulta
     * @return array<int, object>|Entity
     */
    public function handleQueryResult(array $result): array|Entity;
}
