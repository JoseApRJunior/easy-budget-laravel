<?php

declare(strict_types=1);

namespace App\Interfaces;

use Illuminate\Database\Eloquent\Model;

/**
 * Interface base simplificada para todos os repositórios
 *
 * Define apenas métodos CRUD básicos essenciais
 */
interface BaseRepositoryInterface
{
    /**
     * Retorna a classe do modelo associado ao repositório
     *
     * @return string Nome da classe do modelo
     */
    public function getModelClass(): string;

    /**
     * Cria uma nova instância do modelo
     *
     * @param array $attributes Atributos iniciais do modelo
     * @return Model Nova instância do modelo
     */
    public function newModel( array $attributes = [] ): Model;

    /**
     * Retorna uma nova instância de query builder para o modelo
     *
     * @return mixed Query builder instance
     */
    public function newQuery(): mixed;

    /**
     * Conta o número total de registros
     *
     * @return int Número de registros
     */
    public function count(): int;

    /**
     * Verifica se existem registros na tabela
     *
     * @return bool True se existem registros, false caso contrário
     */
    public function exists(): bool;

    /**
     * Trunca a tabela (remove todos os registros)
     *
     * @return bool True se bem-sucedido, false caso contrário
     */
    public function truncate(): bool;

    /**
     * Encontra um registro por ID
     *
     * @param int|string $id ID do registro
     * @return Model|null Registro encontrado ou null
     */
    public function first(): ?Model;

    /**
     * Encontra o último registro
     *
     * @return Model|null Último registro ou null
     */
    public function last(): ?Model;

    /**
     * Executa uma transação de banco de dados
     *
     * @param callable $callback Função a ser executada dentro da transação
     * @return mixed Retorno da função callback
     */
    public function transaction( callable $callback ): mixed;

    /**
     * Inicia uma transação manual
     *
     * @return void
     */
    public function beginTransaction(): void;

    /**
     * Confirma uma transação manual
     *
     * @return void
     */
    public function commit(): void;

    /**
     * Desfaz uma transação manual
     *
     * @return void
     */
    public function rollback(): void;

    /**
     * Realiza refresh dos dados da entidade a partir do banco
     *
     * @param Model $entity Entidade a ser atualizada
     * @return Model|null Entidade atualizada ou null se não encontrada
     */
    public function refresh( Model $entity ): ?Model;
}
