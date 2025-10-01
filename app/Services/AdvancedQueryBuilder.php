<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Advanced Query Builder para relatórios
 * Permite construção fluente e segura de consultas complexas
 */
class AdvancedQueryBuilder
{
    private array   $selects          = [];
    private array   $joins            = [];
    private array   $wheres           = [];
    private array   $whereIns         = [];
    private array   $whereNulls       = [];
    private array   $whereNotNulls    = [];
    private array   $whereDates       = [];
    private array   $groupings        = [];
    private array   $orderings        = [];
    private array   $aggregations     = [];
    private array   $havingConditions = [];
    private ?string $baseTable        = null;
    private array   $tableAliases     = [];
    private array   $subqueryAliases  = [];
    private int     $paramCount       = 0;
    private array   $parameters       = [];

    /**
     * Define a tabela base para a consulta
     */
    public function from( string $table, ?string $alias = null ): self
    {
        $this->baseTable = $table;
        if ( $alias ) {
            $this->tableAliases[ $table ] = $alias;
        }
        return $this;
    }

    /**
     * Adiciona campos à seleção
     */
    public function select( string $expression, ?string $alias = null ): self
    {
        $this->selects[] = compact( 'expression', 'alias' );
        return $this;
    }

    /**
     * Adiciona seleção raw
     */
    public function selectRaw( string $expression, array $bindings = [] ): self
    {
        $this->selects[] = [
            'expression' => $expression,
            'alias'      => null,
            'raw'        => true,
            'bindings'   => $bindings
        ];
        return $this;
    }

    /**
     * Adiciona agregação à seleção
     */
    public function aggregate( string $function, string $column, ?string $alias = null ): self
    {
        $this->aggregations[] = compact( 'function', 'column', 'alias' );
        return $this;
    }

    /**
     * Adiciona condição WHERE simples
     */
    public function where( string $column, string $operator, $value ): self
    {
        $this->wheres[] = compact( 'column', 'operator', 'value' );
        return $this;
    }

    /**
     * Adiciona condição WHERE com múltiplos valores
     */
    public function whereIn( string $column, array $values ): self
    {
        $this->whereIns[] = compact( 'column', 'values' );
        return $this;
    }

    /**
     * Adiciona condição WHERE NULL
     */
    public function whereNull( string $column ): self
    {
        $this->whereNulls[] = $column;
        return $this;
    }

    /**
     * Adiciona condição WHERE NOT NULL
     */
    public function whereNotNull( string $column ): self
    {
        $this->whereNotNulls[] = $column;
        return $this;
    }

    /**
     * Adiciona condição WHERE entre datas
     */
    public function whereBetween( string $column, $startDate, $endDate ): self
    {
        $this->whereDates[] = [
            'column' => $column,
            'start'  => $startDate,
            'end'    => $endDate,
            'type'   => 'between'
        ];
        return $this;
    }

    /**
     * Adiciona condição WHERE por período
     */
    public function whereDate( string $column, string $period = 'today' ): self
    {
        $this->whereDates[] = [
            'column' => $column,
            'period' => $period,
            'type'   => 'period'
        ];
        return $this;
    }

    /**
     * Adiciona JOIN à consulta
     */
    public function join( string $table, string $first, string $operator, string $second, string $type = 'INNER' ): self
    {
        $this->joins[] = compact( 'table', 'first', 'operator', 'second', 'type' );
        return $this;
    }

    /**
     * Adiciona LEFT JOIN à consulta
     */
    public function leftJoin( string $table, string $first, string $operator, string $second ): self
    {
        return $this->join( $table, $first, $operator, $second, 'LEFT' );
    }

    /**
     * Adiciona RIGHT JOIN à consulta
     */
    public function rightJoin( string $table, string $first, string $operator, string $second ): self
    {
        return $this->join( $table, $first, $operator, $second, 'RIGHT' );
    }

    /**
     * Define agrupamento
     */
    public function groupBy( string $column ): self
    {
        $this->groupings[] = $column;
        return $this;
    }

    /**
     * Define ordenação
     */
    public function orderBy( string $column, string $direction = 'ASC' ): self
    {
        $this->orderings[] = compact( 'column', 'direction' );
        return $this;
    }

    /**
     * Adiciona condição HAVING
     */
    public function having( string $column, string $operator, $value ): self
    {
        $this->havingConditions[] = compact( 'column', 'operator', 'value' );
        return $this;
    }

    /**
     * Adiciona subquery como tabela
     */
    public function addSubquery( string $subquery, string $alias ): self
    {
        $this->subqueryAliases[] = compact( 'subquery', 'alias' );
        return $this;
    }

    /**
     * Define limite de resultados
     */
    public function limit( int $limit ): self
    {
        $this->parameters[ 'limit' ] = $limit;
        return $this;
    }

    /**
     * Define offset
     */
    public function offset( int $offset ): self
    {
        $this->parameters[ 'offset' ] = $offset;
        return $this;
    }

    /**
     * Constrói a consulta SQL
     */
    public function build(): string
    {
        if ( !$this->baseTable ) {
            throw new InvalidArgumentException( 'Tabela base não definida. Use o método from() primeiro.' );
        }

        $sql = 'SELECT ';

        // Construir SELECT com agregações
        $selectParts = [];

        foreach ( $this->aggregations as $agg ) {
            $alias         = $agg[ 'alias' ] ?? "{$agg[ 'function' ]}_{$agg[ 'column' ]}";
            $selectParts[] = "{$agg[ 'function' ]}({$agg[ 'column' ]}) AS {$alias}";
        }

        // Adicionar selects regulares
        foreach ( $this->selects as $select ) {
            if ( isset( $select[ 'raw' ] ) ) {
                $selectParts[] = $select[ 'expression' ];
            } else {
                $expression = $select[ 'expression' ];
                if ( $select[ 'alias' ] ) {
                    $expression .= " AS {$select[ 'alias' ]}";
                }
                $selectParts[] = $expression;
            }
        }

        if ( empty( $selectParts ) ) {
            $sql .= '*';
        } else {
            $sql .= implode( ', ', $selectParts );
        }

        // Adicionar FROM
        $tableName = $this->getTableName( $this->baseTable );
        $sql .= " FROM {$tableName}";

        // Adicionar JOINs
        foreach ( $this->joins as $join ) {
            $joinTable = $this->getTableName( $join[ 'table' ] );
            $sql .= " {$join[ 'type' ]} JOIN {$joinTable} ON {$join[ 'first' ]} {$join[ 'operator' ]} {$join[ 'second' ]}";
        }

        // Adicionar subqueries como tabelas
        foreach ( $this->subqueryAliases as $subquery ) {
            $sql .= ", ({$subquery[ 'subquery' ]}) AS {$subquery[ 'alias' ]}";
        }

        // Construir WHERE
        $whereParts = [];
        $bindings   = [];

        // WHERE simples
        foreach ( $this->wheres as $where ) {
            $paramName            = $this->getNextParamName();
            $whereParts[]         = "{$where[ 'column' ]} {$where[ 'operator' ]} :{$paramName}";
            $bindings[ $paramName ] = $where[ 'value' ];
        }

        // WHERE IN
        foreach ( $this->whereIns as $whereIn ) {
            $paramName            = $this->getNextParamName();
            $placeholders         = implode( ',', array_fill( 0, count( $whereIn[ 'values' ] ), ":{$paramName}" ) );
            $whereParts[]         = "{$whereIn[ 'column' ]} IN ({$placeholders})";
            $bindings[ $paramName ] = $whereIn[ 'values' ];
        }

        // WHERE NULL/NOT NULL
        foreach ( $this->whereNulls as $column ) {
            $whereParts[] = "{$column} IS NULL";
        }

        foreach ( $this->whereNotNulls as $column ) {
            $whereParts[] = "{$column} IS NOT NULL";
        }

        // WHERE entre datas
        foreach ( $this->whereDates as $whereDate ) {
            if ( $whereDate[ 'type' ] === 'between' ) {
                $startParam            = $this->getNextParamName();
                $endParam              = $this->getNextParamName();
                $whereParts[]          = "{$whereDate[ 'column' ]} BETWEEN :{$startParam} AND :{$endParam}";
                $bindings[ $startParam ] = $whereDate[ 'start' ];
                $bindings[ $endParam ]   = $whereDate[ 'end' ];
            } elseif ( $whereDate[ 'type' ] === 'period' ) {
                [ $startDate, $endDate ] = $this->getPeriodDates( $whereDate[ 'period' ] );
                $startParam            = $this->getNextParamName();
                $endParam              = $this->getNextParamName();
                $whereParts[]          = "{$whereDate[ 'column' ]} BETWEEN :{$startParam} AND :{$endParam}";
                $bindings[ $startParam ] = $startDate;
                $bindings[ $endParam ]   = $endDate;
            }
        }

        if ( !empty( $whereParts ) ) {
            $sql .= ' WHERE ' . implode( ' AND ', $whereParts );
        }

        // Adicionar GROUP BY
        if ( !empty( $this->groupings ) ) {
            $sql .= ' GROUP BY ' . implode( ', ', $this->groupings );
        }

        // Adicionar HAVING
        if ( !empty( $this->havingConditions ) ) {
            $havingParts = [];
            foreach ( $this->havingConditions as $having ) {
                $paramName            = $this->getNextParamName();
                $havingParts[]        = "{$having[ 'column' ]} {$having[ 'operator' ]} :{$paramName}";
                $bindings[ $paramName ] = $having[ 'value' ];
            }
            $sql .= ' HAVING ' . implode( ' AND ', $havingParts );
        }

        // Adicionar ORDER BY
        if ( !empty( $this->orderings ) ) {
            $orderParts = [];
            foreach ( $this->orderings as $order ) {
                $orderParts[] = "{$order[ 'column' ]} {$order[ 'direction' ]}";
            }
            $sql .= ' ORDER BY ' . implode( ', ', $orderParts );
        }

        // Adicionar LIMIT e OFFSET
        if ( isset( $this->parameters[ 'limit' ] ) ) {
            $sql .= ' LIMIT ' . $this->parameters[ 'limit' ];
        }

        if ( isset( $this->parameters[ 'offset' ] ) ) {
            $sql .= ' OFFSET ' . $this->parameters[ 'offset' ];
        }

        $this->parameters[ 'sql' ]      = $sql;
        $this->parameters[ 'bindings' ] = $bindings;

        return $sql;
    }

    /**
     * Executa a consulta e retorna os resultados
     */
    public function get(): Collection
    {
        $sql      = $this->build();
        $bindings = $this->parameters[ 'bindings' ] ?? [];

        return collect( DB::select( $sql, $bindings ) );
    }

    /**
     * Retorna apenas o primeiro resultado
     */
    public function first()
    {
        $this->limit( 1 );
        $results = $this->get();
        return $results->first();
    }

    /**
     * Retorna o SQL e bindings para debug
     */
    public function getDebugInfo(): array
    {
        return [
            'sql'          => $this->parameters[ 'sql' ] ?? null,
            'bindings'     => $this->parameters[ 'bindings' ] ?? [],
            'selects'      => $this->selects,
            'joins'        => $this->joins,
            'wheres'       => $this->wheres,
            'aggregations' => $this->aggregations
        ];
    }

    /**
     * Obtém nome da tabela com alias se definido
     */
    private function getTableName( string $table ): string
    {
        if ( isset( $this->tableAliases[ $table ] ) ) {
            return "{$table} AS {$this->tableAliases[ $table ]}";
        }
        return $table;
    }

    /**
     * Gera próximo nome de parâmetro
     */
    private function getNextParamName(): string
    {
        return 'param' . $this->paramCount++;
    }

    /**
     * Obtém datas baseadas no período
     */
    private function getPeriodDates( string $period ): array
    {
        $now = now();

        return match ( $period ) {
            'today'      => [ $now->toDateString(), $now->toDateString() ],
            'yesterday'  => [ $now->subDay()->toDateString(), $now->subDay()->toDateString() ],
            'this_week'  => [ $now->startOfWeek()->toDateString(), $now->endOfWeek()->toDateString() ],
            'last_week'  => [ $now->subWeek()->startOfWeek()->toDateString(), $now->subWeek()->endOfWeek()->toDateString() ],
            'this_month' => [ $now->startOfMonth()->toDateString(), $now->endOfMonth()->toDateString() ],
            'last_month' => [ $now->subMonth()->startOfMonth()->toDateString(), $now->subMonth()->endOfMonth()->toDateString() ],
            'this_year'  => [ $now->startOfYear()->toDateString(), $now->endOfYear()->toDateString() ],
            'last_year'  => [ $now->subYear()->startOfYear()->toDateString(), $now->subYear()->endOfYear()->toDateString() ],
            default      => [ $now->toDateString(), $now->toDateString() ]
        };
    }

    /**
     * Cria instância para relatórios financeiros
     */
    public static function financial(): self
    {
        return ( new self() )
            ->from( 'budgets' )
            ->select( 'budgets.id' )
            ->select( 'budgets.name' )
            ->select( 'budgets.total_value' )
            ->select( 'budgets.status' )
            ->select( 'budgets.created_at' )
            ->leftJoin( 'customers', 'budgets.customer_id', '=', 'customers.id' )
            ->select( 'customers.name as customer_name' )
            ->select( 'customers.email as customer_email' );
    }

    /**
     * Cria instância para relatórios de clientes
     */
    public static function customers(): self
    {
        return ( new self() )
            ->from( 'customers' )
            ->select( 'customers.id' )
            ->select( 'customers.name' )
            ->select( 'customers.email' )
            ->select( 'customers.phone' )
            ->select( 'customers.type' )
            ->select( 'customers.created_at' )
            ->leftJoin( 'customer_addresses', 'customers.id', '=', 'customer_addresses.customer_id' )
            ->select( 'customer_addresses.city' )
            ->select( 'customer_addresses.state' );
    }

    /**
     * Cria instância para relatórios de orçamentos
     */
    public static function budgets(): self
    {
        return ( new self() )
            ->from( 'budgets' )
            ->select( 'budgets.id' )
            ->select( 'budgets.name' )
            ->select( 'budgets.total_value' )
            ->select( 'budgets.status' )
            ->select( 'budgets.created_at' )
            ->select( 'budgets.due_date' )
            ->leftJoin( 'customers', 'budgets.customer_id', '=', 'customers.id' )
            ->select( 'customers.name as customer_name' )
            ->leftJoin( 'budget_items', 'budgets.id', '=', 'budget_items.budget_id' )
            ->selectRaw( 'COUNT(budget_items.id) as item_count' )
            ->selectRaw( 'SUM(budget_items.quantity * budget_items.unit_price) as calculated_total' );
    }

    /**
     * Reseta o builder para reutilização
     */
    public function reset(): self
    {
        $this->selects          = [];
        $this->joins            = [];
        $this->wheres           = [];
        $this->whereIns         = [];
        $this->whereNulls       = [];
        $this->whereNotNulls    = [];
        $this->whereDates       = [];
        $this->groupings        = [];
        $this->orderings        = [];
        $this->aggregations     = [];
        $this->havingConditions = [];
        $this->tableAliases     = [];
        $this->subqueryAliases  = [];
        $this->paramCount       = 0;
        $this->parameters       = [];

        return $this;
    }

}
