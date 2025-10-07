<?php

namespace app\interfaces;

use JsonSerializable;

/**
 * Interface EntityORMInterface
 *
 * Define o contrato para entidades que precisam ser gerenciadas por um ORM (Object-Relational Mapping).
 * Esta interface deve ser implementada por classes que representam entidades do banco de dados
 * e precisam ser persistidas usando funcionalidades ORM.
 *
 * Todas as entidades ORM devem implementar JsonSerializable para garantir
 * serialização consistente e evitar problemas com proxies do Doctrine.
 *
 * @package app\interfaces
 */
/**
 * @method int|null getId() Returns the entity ID.
 * @method void setTenantId(int $tenantId) Sets the tenant ID.
 * @method int|null getTenantId() Returns the tenant ID.
 * @method object|null getTenant() Returns the associated tenant entity.
 * @method bool isActive() Returns if the entity is active.
 * @method void setActive(bool $active) Sets the active status.
 * @method string getSlug() Returns the entity slug.
 * @method void setSlug(string $slug) Sets the entity slug.
 */
interface EntityORMInterface extends JsonSerializable
{
    /**
     * Cria uma nova instância da entidade a partir de um array de propriedades.
     *
     * Este método deve ser implementado por todas as entidades ORM para
     * permitir a criação de instâncias a partir de dados do banco de dados
     * ou de arrays associativos.
     *
     * @param array<string, mixed> $properties Array associativo com as propriedades para inicializar a entidade.
     * @return static Uma nova instância da entidade.
     * @throws \Exception Se houver erro ao processar as propriedades.
     */
    public static function create( array $properties ): static;

    /**
     * Serializa a entidade para JSON.
     *
     * Este método é usado para converter a entidade em um array
     * que pode ser serializado com segurança para JSON, evitando
     * problemas com proxies do Doctrine e referências circulares.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array;
}