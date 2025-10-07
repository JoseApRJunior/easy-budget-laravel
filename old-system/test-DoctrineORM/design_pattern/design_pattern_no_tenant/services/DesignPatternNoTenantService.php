<?php

namespace design_patern\design_pattern_no_tenant\services;

use app\interfaces\ServiceNoTenantInterface;
use app\support\ServiceResult;
use core\dbal\EntityNotFound;
use app\enums\OperationStatus;
use Exception;
use design_patern\design_pattern_no_tenant\entities\DesignPatternNoTenantEntity;
use design_patern\design_pattern_no_tenant\repositories\DesignPatternNoTenantRepository;

/**
 * Padrão de Service NoTenant - Easy Budget
 *
 * PADRÕES IMPLEMENTADOS:
 * ✅ Sempre retorna ServiceResult - Consistência em todo o projeto
 * ✅ Implementa ServiceNoTenantInterface - Padronização de contratos
 * ✅ Encapsula regras de negócio - Lógica centralizada
 * ✅ Validação de dados consistente - Métodos validate()
 * ✅ Comentários em português brasileiro - Padrão do projeto
 * ✅ Tratamento estruturado de erros - Status e mensagens padronizadas
 * ✅ Trabalha com retorno EntityORMInterface|false dos repositories
 * ✅ Mensagens contextuais específicas - "Design Pattern" em vez de "Entidade"
 * ✅ Verificação aprimorada de null - Tratamento consistente de resultados vazios
 *
 * BENEFÍCIOS:
 * - Encapsulamento estruturado de status, mensagens e dados
 * - Consistência na manipulação de erros
 * - Fácil integração com controllers
 * - Reutilização de lógica de negócio
 * - Mensagens mais intuitivas para o usuário
 * - Não requer controle de tenant (adequado para entidades globais)
 */
class DesignPatternNoTenantService implements ServiceNoTenantInterface
{
    private DesignPatternNoTenantRepository $designPatternRepository;

    public function __construct( DesignPatternNoTenantRepository $designPatternRepository )
    {
        $this->designPatternRepository = $designPatternRepository;
    }

    /**
     * Busca uma entidade pelo ID.
     *
     * @param int $id ID da entidade
     * @return ServiceResult Resultado com entidade ou erro
     */
    public function getById( int $id ): ServiceResult
    {
        try {
            $entity = $this->designPatternRepository->findById( $id );

            if ( $entity === null ) {
                return ServiceResult::error( OperationStatus::NOT_FOUND, 'Design Pattern não encontrada.' );
            }

            return ServiceResult::success( $entity, 'Design Pattern encontrada com sucesso.' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Falha ao buscar Design Pattern: ' . $e->getMessage() );
        }
    }

    /**
     * Lista todas as entidades com filtros opcionais.
     *
     * ATUALIZAÇÃO: Verificação aprimorada de null e mensagens específicas
     *
     * @param array<string, mixed> $filters Filtros de busca
     * @return ServiceResult Resultado com lista de entidades
     */
    public function list( array $filters = [] ): ServiceResult
    {
        try {
            $criteria = [];
            $orderBy  = [ 'name' => 'ASC' ];

            // Aplicar filtros conforme necessário
            if ( !empty( $filters[ 'name' ] ) ) {
                $criteria[ 'name' ] = $filters[ 'name' ];
            }

            if ( !empty( $filters[ 'active' ] ) ) {
                $criteria[ 'active' ] = $filters[ 'active' ];
            }

            $entities = $this->designPatternRepository->findAll( $criteria, $orderBy );

            // Retorna sucesso mesmo que a lista esteja vazia (é um resultado válido)
            if ( $entities === null ) {
                return ServiceResult::error( OperationStatus::NOT_FOUND, 'Design Pattern não encontrado.' );
            }
            return ServiceResult::success( $entities, 'Design Pattern encontrado com sucesso.' );

        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Falha ao listar entidades: ' . $e->getMessage() );
        }
    }

    /**
     * Cria uma nova entidade.
     *
     * @param array<string, mixed> $data Dados para criação
     * @return ServiceResult Resultado da operação
     */
    public function create( array $data ): ServiceResult
    {
        try {
            // Validar dados de entrada
            $validation = $this->validate( $data );
            if ( !$validation->isSuccess() ) {
                return $validation;
            }

            // Criar nova entidade
            $entity = new DesignPatternNoTenantEntity();
            $entity->setName( $data[ 'name' ] );

            if ( isset( $data[ 'description' ] ) ) {
                $entity->setDescription( $data[ 'description' ] );
            }

            // Gerar slug automaticamente se não fornecido
            if ( isset( $data[ 'slug' ] ) ) {
                $entity->setSlug( $data[ 'slug' ] );
            } else {
                $generatedSlug = $this->generateSlug( $data[ 'name' ] );
                $entity->setSlug( $generatedSlug );
            }

            if ( isset( $data[ 'active' ] ) ) {
                $entity->setActive( (bool) $data[ 'active' ] );
            }

            // Salvar via repository (retorna EntityORMInterface|false)
            $result = $this->designPatternRepository->save( $entity );

            if ( $result !== false ) {
                // Retorna sucesso com a entidade completa (com ID, timestamps, etc.)
                return ServiceResult::success( $result, 'Design Pattern criada com sucesso.' );
            } else {
                return ServiceResult::error( OperationStatus::ERROR, 'Falha ao salvar Design Pattern no banco de dados.' );
            }
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Falha ao criar Design Pattern: ' . $e->getMessage() );
        }
    }

    /**
     * Atualiza uma entidade existente.
     *
     * @param int $id ID da entidade
     * @param array<string, mixed> $data Dados para atualização
     * @return ServiceResult Resultado da operação
     */
    public function update( int $id, array $data ): ServiceResult
    {
        try {
            // Validar dados de entrada
            $validation = $this->validate( $data, true );
            if ( !$validation->isSuccess() ) {
                return $validation;
            }

            // Buscar entidade existente
            $entity = $this->designPatternRepository->findById( $id );
            if ( $entity instanceof EntityNotFound || $entity === null ) {
                return ServiceResult::error( OperationStatus::NOT_FOUND, 'Design Pattern não encontrada.' );
            }

            // Atualizar dados
            /** @var DesignPatternNoTenantEntity $entity */
            $oldName = $entity->getName();
            $entity->setName( $data[ 'name' ] );

            if ( isset( $data[ 'description' ] ) ) {
                $entity->setDescription( $data[ 'description' ] );
            }

            // Atualizar slug se necessário
            if ( isset( $data[ 'slug' ] ) ) {
                $entity->setSlug( $data[ 'slug' ] );
            } elseif ( $oldName !== $data[ 'name' ] ) {
                $entity->setSlug( $this->generateSlug( $data[ 'name' ] ) );
            }

            if ( isset( $data[ 'active' ] ) ) {
                $entity->setActive( (bool) $data[ 'active' ] );
            }

            // Salvar via repository
            $result = $this->designPatternRepository->save( $entity );

            if ( $result !== false ) {
                return ServiceResult::success( $result, 'Design Pattern atualizada com sucesso.' );
            } else {
                return ServiceResult::error( OperationStatus::ERROR, 'Falha ao salvar Design Pattern no banco de dados.' );
            }
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Falha ao atualizar Design Pattern: ' . $e->getMessage() );
        }
    }

    /**
     * Remove uma entidade.
     *
     * @param int $id ID da entidade
     * @return ServiceResult Resultado da operação
     */
    public function delete( int $id ): ServiceResult
    {
        try {
            // Verificar se a entidade existe antes de deletar
            $entity = $this->designPatternRepository->findById( $id );
            if ( $entity === null ) {
                return ServiceResult::error( OperationStatus::NOT_FOUND, 'Design Pattern não encontrada.' );
            }

            // Executar exclusão via repository (retorna bool)
            $result = $this->designPatternRepository->delete( $id );

            if ( $result ) {
                return ServiceResult::success( null, 'Entidade removida com sucesso.' );
            } else {
                return ServiceResult::error( OperationStatus::ERROR, 'Falha ao remover Design Pattern do banco de dados.' );
            }
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Falha ao excluir Design Pattern: ' . $e->getMessage() );
        }
    }

    /**
     * Valida os dados de entrada para operações de criação ou atualização.
     *
     * @param array<string, mixed> $data Dados a serem validados
     * @param bool $isUpdate Indica se é uma operação de atualização
     * @return ServiceResult Resultado da validação
     */
    public function validate( array $data, bool $isUpdate = false ): ServiceResult
    {
        $errors = [];

        // Validar nome
        if ( empty( $data[ 'name' ] ) ) {
            $errors[] = "O nome da entidade é obrigatório.";
        } elseif ( strlen( $data[ 'name' ] ) > 100 ) {
            $errors[] = "O nome da entidade deve ter no máximo 100 caracteres.";
        }

        // Validar slug (se fornecido)
        if ( isset( $data[ 'slug' ] ) ) {
            if ( empty( $data[ 'slug' ] ) ) {
                $errors[] = "O slug não pode estar vazio quando fornecido.";
            } elseif ( strlen( $data[ 'slug' ] ) > 100 ) {
                $errors[] = "O slug deve ter no máximo 100 caracteres.";
            } elseif ( !preg_match( '/^[a-z0-9-]+$/', $data[ 'slug' ] ) ) {
                $errors[] = "O slug deve conter apenas letras minúsculas, números e hífens.";
            }
        }

        // Validar descrição (se fornecida)
        if ( isset( $data[ 'description' ] ) && strlen( $data[ 'description' ] ) > 500 ) {
            $errors[] = "A descrição deve ter no máximo 500 caracteres.";
        }

        if ( count( $errors ) > 0 ) {
            return ServiceResult::error( OperationStatus::INVALID_DATA, "Dados inválidos: " . implode( ', ', $errors ) );
        }

        return ServiceResult::success( null, "Dados válidos." );
    }

    /**
     * Gera um slug a partir do nome.
     *
     * @param string $name Nome da entidade
     * @return string Slug gerado
     */
    private function generateSlug( string $name ): string
    {
        // Converter para minúsculas
        $slug = mb_strtolower( trim( $name ), 'UTF-8' );

        // Remover acentos
        $slug = iconv( 'UTF-8', 'ASCII//TRANSLIT', $slug );

        // Substituir espaços e caracteres especiais por hífens
        $slug = preg_replace( '/[^a-z0-9]+/', '-', $slug );

        // Remover hífens duplos e das extremidades
        $slug = trim( preg_replace( '/-+/', '-', $slug ), '-' );

        return $slug;
    }

}

/*
EXEMPLOS DE USO NO TENANT:

1. Criação via Service (Recomendado)
$serviceResult = $designPatternService->create([
    'name' => 'Nova Entidade',
    'description' => 'Descrição da entidade',
    'active' => true
]);

if ($serviceResult->isSuccess()) {
    $entity = $serviceResult->data; // Entidade completa com ID, timestamps, slug
    echo "Entidade criada: {$entity->getName()} (ID: {$entity->getId()})";
    echo "Slug gerado: {$entity->getSlug()}";
    echo "Criado em: {$entity->getCreatedAt()->format('Y-m-d H:i:s')}";
} else {
    echo "Erro: {$serviceResult->message}";
}

2. Lista com filtros
$serviceResult = $designPatternService->list(['name' => 'termo-busca']);

if ($serviceResult->isSuccess()) {
    $entities = $serviceResult->data;
    foreach ($entities as $entity) {
        echo "- {$entity->getName()}";
    }
}

3. Atualização
$serviceResult = $designPatternService->update($id, [
    'name' => 'Nome Atualizado',
    'description' => 'Nova descrição'
]);

if ($serviceResult->isSuccess()) {
    $entity = $serviceResult->data;
    echo "Atualizado: {$entity->getUpdatedAt()->format('Y-m-d H:i:s')}";
}

4. Exclusão
$serviceResult = $designPatternService->delete($id);

if ($serviceResult->isSuccess()) {
    echo "Entidade removida com sucesso!";
} else {
    echo "Erro: {$serviceResult->message}";
}
*/
