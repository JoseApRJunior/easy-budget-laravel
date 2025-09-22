<?php

declare(strict_types=1);

namespace App\Interfaces;

/**
 * Interface base que deve ser implementada por todas as entidades ORM
 *
 * Esta interface garante que todas as entidades tenham métodos básicos
 * necessários para operações com o ORM e compatibilidade com repositórios
 */
interface EntityORMInterface
{
    /**
     * Retorna o identificador único da entidade
     *
     * @return mixed O ID da entidade (pode ser int, string/UUID, etc.)
     */
    public function getId(): mixed;

    /**
     * Define o identificador único da entidade
     *
     * @param int $id O ID a ser definido
     * @return self
     */
    public function setId( int $id ): self;

    /**
     * Converte a entidade para array
     *
     * @return array Representação da entidade em array
     */
    public function toArray(): array;

    /**
     * Verifica se a entidade já foi persistida no banco
     *
     * @return bool True se a entidade existe no banco, false caso contrário
     */
    public function exists(): bool;

    /**
     * Retorna os atributos que foram modificados desde a última sincronização
     *
     * @return array Array com os atributos modificados
     */
    public function getDirty(): array;

    /**
     * Verifica se a entidade foi modificada
     *
     * @return bool True se foi modificada, false caso contrário
     */
    public function isDirty(): bool;

    /**
     * Marca todos os atributos como sincronizados com o banco
     *
     * @return self
     */
    public function syncOriginal(): self;

    /**
     * Retorna a data de criação da entidade
     *
     * @return \DateTime|null Data de criação ou null se não definida
     */
    public function getCreatedAt(): ?\DateTime;

    /**
     * Retorna a data de última atualização da entidade
     *
     * @return \DateTime|null Data de atualização ou null se não definida
     */
    public function getUpdatedAt(): ?\DateTime;
}
