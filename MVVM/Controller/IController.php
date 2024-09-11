<?php

namespace MVVM\Controller;


// Controller-Interface, das die CRUD-Methoden für die Entitäten vorgibt.
/**
 * @template T
 */
interface IController {
    /**
     * @param int $id
     * @return T|null
     */
    public function getById(int $id): ?object;

    /**
     * @return T[]
     */
    public function getAll(): array;

    /**
     * @param T $model
     * @return void
     */
    public function create(object $model): void;

    /**
     * @param T $model
     * @return void
     */
    public function update(object $model): void;

    /**
     * @param int $id
     * @return void
     */
    public function delete(int $id): void;

    /**
     * @param string $criteria
     * @return T[]
     */

    /**
     * @param string $endpoint
     * @return mixed
     */
    public function getApiData(string $endpoint);
}
