<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OperationStatus;
use App\Models\Report;
use App\Repositories\ReportRepository;
use App\Services\Abstracts\BaseTenantService;
use App\Support\ServiceResult;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 * Serviço para armazenamento e gerenciamento de relatórios.
 *
 * Migra funcionalidades do legacy ReportStorageService para Laravel,
 * utilizando Storage facade para operações de arquivo e mantendo
 * compatibilidade com métodos legacy. Implementa tenant isolation
 * para garantir que relatórios sejam isolados por tenant.
 *
 * Funcionalidades principais:
 * - CRUD completo de relatórios com tenant isolation
 * - Armazenamento de arquivos usando Laravel Storage
 * - Geração de hash para evitar duplicatas
 * - Validação de dados e arquivos
 * - Compatibilidade com API legacy
 */
class ReportStorageService extends BaseTenantService
{
    /**
     * Repositório para operações de relatório.
     *
     * @var ReportRepository
     */
    private ReportRepository $reportRepository;

    /**
     * Usuário autenticado atual.
     *
     * @var mixed
     */
    private mixed $authenticatedUser;

    /**
     * Configuração para relatórios.
     *
     * @var array<string, mixed>
     */
    private array $config;

    /**
     * Construtor com injeção de dependências.
     *
     * @param ReportRepository $reportRepository Repositório de relatórios
     */
    public function __construct( ReportRepository $reportRepository )
    {
        $this->reportRepository = $reportRepository;

        // Obtém usuário autenticado
        $this->authenticatedUser = Auth::user();

        // Carrega configuração de relatórios
        $this->config = config( 'report', [ 
            'allowed_formats' => [ 'pdf', 'csv', 'xlsx' ],
            'max_size'        => 10 * 1024 * 1024, // 10MB
            'storage_disk'    => 'local',
            'base_path'       => 'reports'
        ] );
    }

    // MÉTODOS ABSTRATOS OBRIGATÓRIOS DA BaseTenantService

    /**
     * Busca um relatório pelo ID e tenant_id.
     *
     * @param int $id ID do relatório
     * @param int $tenant_id ID do tenant
     * @return ServiceResult
     */
    public function getByIdAndTenantId( int $id, int $tenant_id ): ServiceResult
    {
        try {
            $report = $this->findEntityByIdAndTenantId( $id, $tenant_id );
            if ( !$report ) {
                return $this->error( OperationStatus::NOT_FOUND, 'Relatório não encontrado.' );
            }

            return $this->success( $report, 'Relatório encontrado com sucesso.' );
        } catch ( Exception $e ) {
            return $this->error( OperationStatus::ERROR, 'Falha ao buscar relatório: ' . $e->getMessage(), null, $e );
        }
    }

    /**
     * Lista relatórios por tenant_id com filtros.
     *
     * @param int $tenant_id ID do tenant
     * @param array $filters Filtros opcionais
     * @param ?array $orderBy Ordem dos resultados
     * @param ?int $limit Limite de resultados
     * @param ?int $offset Offset dos resultados
     * @return ServiceResult
     */
    public function listByTenantId( int $tenant_id, array $filters = [], ?array $orderBy = null, ?int $limit = null, ?int $offset = null ): ServiceResult
    {
        try {
            $reports = $this->listEntitiesByTenantId( $tenant_id, $filters, $orderBy, $limit, $offset );
            return $this->success( $reports, 'Relatórios listados com sucesso.' );
        } catch ( Exception $e ) {
            return $this->error( OperationStatus::ERROR, 'Falha ao listar relatórios: ' . $e->getMessage(), null, $e );
        }
    }

    /**
     * Cria relatório para tenant_id.
     *
     * @param array $data Dados do relatório
     * @param int $tenant_id ID do tenant
     * @return ServiceResult
     */
    public function createByTenantId( array $data, int $tenant_id ): ServiceResult
    {
        try {
            // Validação específica
            $validation = $this->validateForTenant( $data, $tenant_id, false );
            if ( !$validation->isSuccess() ) {
                return $validation;
            }

            \DB::beginTransaction();

            $report = $this->createEntity( $data, $tenant_id );
            $saved  = $this->saveEntity( $report );

            if ( !$saved ) {
                \DB::rollBack();
                return $this->error( OperationStatus::ERROR, 'Falha ao criar relatório.' );
            }

            \DB::commit();
            return $this->success( $report, 'Relatório criado com sucesso.' );
        } catch ( Exception $e ) {
            \DB::rollBack();
            return $this->error( OperationStatus::ERROR, 'Falha ao criar relatório: ' . $e->getMessage(), null, $e );
        }
    }

    /**
     * Atualiza relatório por ID e tenant_id.
     *
     * @param int $id ID do relatório
     * @param int $tenant_id ID do tenant
     * @param array $data Dados de atualização
     * @return ServiceResult
     */
    public function updateByIdAndTenantId( int $id, array $data, int $tenantId ): ServiceResult
    {
        try {
            $report = $this->findEntityByIdAndTenantId( $id, $tenant_id );
            if ( !$report ) {
                return $this->error( OperationStatus::NOT_FOUND, 'Relatório não encontrado.' );
            }

            // Validação específica
            $validation = $this->validateForTenant( $data, $tenant_id, true );
            if ( !$validation->isSuccess() ) {
                return $validation;
            }

            \DB::beginTransaction();

            $this->updateEntity( $report, $data, $tenant_id );
            $saved = $this->saveEntity( $report );

            if ( !$saved ) {
                \DB::rollBack();
                return $this->error( OperationStatus::ERROR, 'Falha ao atualizar relatório.' );
            }

            \DB::commit();
            return $this->success( $report, 'Relatório atualizado com sucesso.' );
        } catch ( Exception $e ) {
            \DB::rollBack();
            return $this->error( OperationStatus::ERROR, 'Falha ao atualizar relatório: ' . $e->getMessage(), null, $e );
        }
    }

    /**
     * Deleta relatório por ID e tenant_id.
     *
     * @param int $id ID do relatório
     * @param int $tenant_id ID do tenant
     * @return ServiceResult
     */
    public function deleteByIdAndTenantId( int $id, int $tenant_id ): ServiceResult
    {
        try {
            $report = $this->findEntityByIdAndTenantId( $id, $tenant_id );
            if ( !$report ) {
                return $this->error( OperationStatus::NOT_FOUND, 'Relatório não encontrado.' );
            }

            \DB::beginTransaction();

            // Remove arquivo físico se existir
            if ( $report->file_path && Storage::disk( $this->config[ 'storage_disk' ] )->exists( $report->file_path ) ) {
                Storage::disk( $this->config[ 'storage_disk' ] )->delete( $report->file_path );
            }

            $deleted = $this->deleteEntity( $report );

            if ( !$deleted ) {
                \DB::rollBack();
                return $this->error( OperationStatus::ERROR, 'Falha ao deletar relatório.' );
            }

            \DB::commit();
            return $this->success( null, 'Relatório deletado com sucesso.' );
        } catch ( Exception $e ) {
            \DB::rollBack();
            return $this->error( OperationStatus::ERROR, 'Falha ao deletar relatório: ' . $e->getMessage(), null, $e );
        }
    }

    // MÉTODOS TEMPLATE SOBRESCRITOS PARA LÓGICA ESPECÍFICA

    /**
     * Encontra relatório por ID e tenant.
     */
    protected function findEntityByIdAndTenantId( int $id, int $tenant_id ): ?\Illuminate\Database\Eloquent\Model
    {
        return $this->reportRepository->findByIdAndTenantId( $id, $tenant_id );
    }

    /**
     * Lista relatórios por tenant com filtros.
     */
    protected function listEntitiesByTenantId( int $tenant_id, array $filters = [], ?array $orderBy = null, ?int $limit = null, ?int $offset = null ): array
    {
        return $this->reportRepository->findAllByTenantId( $tenant_id, $filters, $orderBy ?? [ 'createdAt' => 'DESC' ], $limit, $offset );
    }

    /**
     * Cria entidade relatório.
     */
    protected function createEntity( array $data, int $tenant_id ): \Illuminate\Database\Eloquent\Model
    {
        $report = new Report();
        $report->fill( [ 
            'tenant_id'   => $tenant_id,
            'user_id'     => $this->authenticatedUser->id ?? $data[ 'user_id' ],
            'hash'        => $data[ 'hash' ] ?? $this->generateReportHash( $data ),
            'type'        => $data[ 'type' ] ?? 'general',
            'description' => $data[ 'description' ] ?? '',
            'file_name'   => $data[ 'file_name' ] ?? '',
            'status'      => $data[ 'status' ] ?? 'pending',
            'format'      => $data[ 'format' ] ?? 'pdf',
            'size'        => (float) ( $data[ 'size' ] ?? 0 ),
        ] );
        /** @var \Illuminate\Database\Eloquent\Model $report */
        return $report;
    }

    /**
     * Atualiza entidade relatório.
     */
    protected function updateEntity( \Illuminate\Database\Eloquent\Model $entity, array $data, int $tenant_id ): void
    {
        $entity->fill( $data );
    }

    /**
     * Salva entidade.
     */
    protected function saveEntity( \Illuminate\Database\Eloquent\Model $entity ): bool
    {
        return $entity->save();
    }

    /**
     * Deleta entidade relatório.
     */
    protected function deleteEntity( \Illuminate\Database\Eloquent\Model $entity ): bool
    {
        return $entity->delete();
    }

    /**
     * Verifica se pertence ao tenant.
     */
    protected function belongsToTenant( \Illuminate\Database\Eloquent\Model $entity, int $tenant_id ): bool
    {
        return $entity->tenant_id === $tenant_id;
    }

    /**
     * Verifica se pode deletar.
     */
    protected function canDeleteEntity( \Illuminate\Database\Eloquent\Model $entity ): bool
    {
        // Pode deletar se não for um relatório crítico do sistema
        return $entity->status !== 'system';
    }

    /**
     * Validação específica para relatório.
     */
    public function validateForTenant( array $data, int $tenant_id, bool $isUpdate = false ): ServiceResult
    {
        $rules = [ 
            'type'        => 'required|string|max:50',
            'description' => 'required|string|max:255',
            'format'      => 'required|string|in:' . implode( ',', $this->config[ 'allowed_formats' ] ),
            'size'        => 'required|numeric|min:0|max:' . $this->config[ 'max_size' ],
        ];

        if ( !$isUpdate ) {
            $rules[ 'file_name' ] = 'required|string|max:255';
        }

        if ( isset( $data[ 'file_name' ] ) ) {
            $rules[ 'file_name' ] = 'string|max:255';
        }

        $validator = Validator::make( $data, $rules );
        if ( $validator->fails() ) {
            $messages = $validator->errors()->all();
            return $this->error( OperationStatus::INVALID_DATA, implode( ', ', $messages ) );
        }

        return $this->success();
    }

    /**
     * Método validate da interface base.
     *
     * @param array $data Dados a serem validados
     * @param bool $isUpdate Indica se é uma operação de atualização
     * @return ServiceResult Resultado da validação
     */
    public function validate( array $data, bool $isUpdate = false ): ServiceResult
    {
        // Redirecionar para validateForTenant com tenant_id padrão
        $tenant_id = $data[ 'tenant_id' ] ?? 0;
        return $this->validateForTenant( $data, $tenant_id, $isUpdate );
    }

    // MÉTODOS ESPECÍFICOS DE RELATÓRIO (MIGRADOS DO LEGACY)

    /**
     * Manipula a geração e armazenamento de relatórios.
     *
     * Método principal migrado do legacy ReportStorageService.
     * Gera hash do relatório, verifica duplicatas e armazena arquivo.
     *
     * @param mixed $content Conteúdo do relatório
     * @param array $data Dados do relatório
     * @return ServiceResult Resultado da operação
     */
    public function handleReport( mixed $content, array $data ): ServiceResult
    {
        try {
            // Valida dados de entrada
            $validation = $this->validateReportData( $data );
            if ( !$validation->isSuccess() ) {
                return $validation;
            }

            // Gera hash do relatório para evitar duplicatas
            $reportHash = $this->generateReportHash( $data );

            // Verifica se existe relatório idêntico recente
            $existingReport = $this->findDuplicateReport( $reportHash, $data[ 'tenant_id' ] );

            if ( $existingReport && !$this->isExpired( $existingReport ) ) {
                return ServiceResult::success( [ 
                    'id'           => $existingReport->id,
                    'file_path'    => $existingReport->file_path,
                    'is_duplicate' => true,
                ], 'Relatório duplicado encontrado.' );
            }

            // Armazena o arquivo
            $storageResult = $this->storeReportFile( $content, $data );
            if ( !$storageResult->isSuccess() ) {
                return $storageResult;
            }

            $fileInfo = $storageResult->getData();

            // Prepara dados para criação do relatório
            $reportData = [ 
                'hash'        => $reportHash,
                'type'        => $data[ 'type' ] ?? 'general',
                'description' => $data[ 'description' ] ?? '',
                'file_name'   => $data[ 'file_name' ] ?? 'report.' . ( $data[ 'format' ] ?? 'pdf' ),
                'status'      => 'completed',
                'format'      => $data[ 'format' ] ?? 'pdf',
                'size'        => $fileInfo[ 'size' ],
                'user_id'     => $this->authenticatedUser->id,
            ];

            // Cria o relatório no banco
            $createResult = $this->createByTenantId( $reportData, $data[ 'tenant_id' ] );

            if ( !$createResult->isSuccess() ) {
                // Remove arquivo se falhou ao salvar no banco
                Storage::disk( $this->config[ 'storage_disk' ] )->delete( $fileInfo[ 'path' ] );
                return $createResult;
            }

            $report            = $createResult->getData();
            $report->file_path = $fileInfo[ 'path' ];

            return ServiceResult::success( [ 
                'id'        => $report->id,
                'file_path' => $report->file_path,
                'hash'      => $reportHash,
                'size'      => $fileInfo[ 'size' ],
            ], 'Relatório criado com sucesso.' );

        } catch ( Exception $e ) {
            return $this->error( OperationStatus::ERROR, 'Erro no método handleReport: ' . $e->getMessage(), null, $e );
        }
    }

    /**
     * Busca relatório por hash para evitar duplicatas.
     *
     * @param string $hash Hash do relatório
     * @param int $tenantId ID do tenant
     * @return Report|null
     */
    private function findDuplicateReport( string $hash, int $tenantId ): ?Report
    {
        return $this->reportRepository->findAllByTenantId( $tenantId, [ 'hash' => $hash ], [ 'createdAt' => 'DESC' ] )
            ->first();
    }

    /**
     * Verifica se um relatório está expirado.
     *
     * @param Report $report Relatório a verificar
     * @return bool True se expirado, false caso contrário
     */
    private function isExpired( Report $report ): bool
    {
        if ( !$report->expires_at ) {
            return false;
        }

        return strtotime( $report->expires_at ) < time();
    }

    /**
     * Gera hash único para o relatório.
     *
     * @param array $data Dados do relatório
     * @return string Hash gerado
     */
    private function generateReportHash( array $data ): string
    {
        $content  = $data[ 'content' ] ?? '';
        $userId   = $this->authenticatedUser->id ?? 0;
        $tenantId = $data[ 'tenant_id' ];

        return hash( 'sha256', $content . $userId . $tenantId . time() );
    }

    /**
     * Armazena arquivo do relatório usando Laravel Storage.
     *
     * @param mixed $content Conteúdo do arquivo
     * @param array $data Dados do relatório
     * @return ServiceResult
     */
    private function storeReportFile( mixed $content, array $data ): ServiceResult
    {
        try {
            // Valida tamanho do arquivo
            $contentSize = strlen( (string) $content );
            if ( $contentSize > $this->config[ 'max_size' ] ) {
                return $this->error( OperationStatus::INVALID_DATA, 'Arquivo muito grande. Tamanho máximo: ' . ( $this->config[ 'max_size' ] / 1024 / 1024 ) . 'MB' );
            }

            // Valida formato
            $format = $data[ 'format' ] ?? 'pdf';
            if ( !in_array( $format, $this->config[ 'allowed_formats' ] ) ) {
                return $this->error( OperationStatus::INVALID_DATA, 'Formato inválido. Formatos permitidos: ' . implode( ', ', $this->config[ 'allowed_formats' ] ) );
            }

            // Cria diretório baseado no tipo e data
            $directory = sprintf(
                '%s/%s/%s',
                $this->config[ 'base_path' ],
                $data[ 'type' ] ?? 'general',
                date( 'Y/m' ),
            );

            // Gera nome único do arquivo
            $fileName = sprintf(
                '%s.%s',
                uniqid( 'report_', true ),
                $format,
            );

            $filePath = $directory . '/' . $fileName;

            // Armazena arquivo
            $stored = Storage::disk( $this->config[ 'storage_disk' ] )->put( $filePath, $content );

            if ( !$stored ) {
                return $this->error( OperationStatus::ERROR, 'Falha ao armazenar arquivo.' );
            }

            return ServiceResult::success( [ 
                'path'   => $filePath,
                'size'   => $contentSize,
                'format' => $format,
            ], 'Arquivo armazenado com sucesso.' );

        } catch ( Exception $e ) {
            return $this->error( OperationStatus::ERROR, 'Erro ao armazenar arquivo: ' . $e->getMessage(), null, $e );
        }
    }

    /**
     * Valida dados do relatório.
     *
     * @param array $data Dados a validar
     * @return ServiceResult
     */
    private function validateReportData( array $data ): ServiceResult
    {
        if ( empty( $data[ 'tenant_id' ] ) ) {
            return $this->error( OperationStatus::INVALID_DATA, 'ID do tenant é obrigatório.' );
        }

        if ( empty( $data[ 'type' ] ) ) {
            return $this->error( OperationStatus::INVALID_DATA, 'Tipo do relatório é obrigatório.' );
        }

        return $this->success();
    }

    /**
     * Obtém conteúdo do arquivo de relatório.
     *
     * @param string $filePath Caminho do arquivo
     * @return ServiceResult
     */
    public function getReportFile( string $filePath ): ServiceResult
    {
        try {
            if ( !Storage::disk( $this->config[ 'storage_disk' ] )->exists( $filePath ) ) {
                return $this->error( OperationStatus::NOT_FOUND, 'Arquivo não encontrado.' );
            }

            $content = Storage::disk( $this->config[ 'storage_disk' ] )->get( $filePath );

            return ServiceResult::success( $content, 'Arquivo recuperado com sucesso.' );
        } catch ( Exception $e ) {
            return $this->error( OperationStatus::ERROR, 'Erro ao recuperar arquivo: ' . $e->getMessage(), null, $e );
        }
    }

    /**
     * Remove arquivo de relatório.
     *
     * @param string $filePath Caminho do arquivo
     * @return ServiceResult
     */
    public function deleteReportFile( string $filePath ): ServiceResult
    {
        try {
            if ( !Storage::disk( $this->config[ 'storage_disk' ] )->exists( $filePath ) ) {
                return $this->error( OperationStatus::NOT_FOUND, 'Arquivo não encontrado.' );
            }

            $deleted = Storage::disk( $this->config[ 'storage_disk' ] )->delete( $filePath );

            if ( !$deleted ) {
                return $this->error( OperationStatus::ERROR, 'Falha ao remover arquivo.' );
            }

            return ServiceResult::success( null, 'Arquivo removido com sucesso.' );
        } catch ( Exception $e ) {
            return $this->error( OperationStatus::ERROR, 'Erro ao remover arquivo: ' . $e->getMessage(), null, $e );
        }
    }

    /**
     * Lista relatórios por tipo.
     *
     * @param string $type Tipo do relatório
     * @param int $tenantId ID do tenant
     * @param ?array $orderBy Ordem dos resultados
     * @return ServiceResult
     */
    public function listByType( string $type, int $tenantId, ?array $orderBy = null ): ServiceResult
    {
        try {
            $reports = $this->reportRepository->findByTypeAndTenantId( $type, $tenantId );
            return ServiceResult::success( $reports, 'Relatórios por tipo listados com sucesso.' );
        } catch ( Exception $e ) {
            return $this->error( OperationStatus::ERROR, 'Falha ao listar relatórios por tipo: ' . $e->getMessage(), null, $e );
        }
    }

    /**
     * Lista relatórios por status.
     *
     * @param string $status Status do relatório
     * @param int $tenantId ID do tenant
     * @param ?array $orderBy Ordem dos resultados
     * @return ServiceResult
     */
    public function listByStatus( string $status, int $tenantId, ?array $orderBy = null ): ServiceResult
    {
        try {
            $reports = $this->reportRepository->findByStatusAndTenantId( $status, $tenantId );
            return ServiceResult::success( $reports, 'Relatórios por status listados com sucesso.' );
        } catch ( Exception $e ) {
            return $this->error( OperationStatus::ERROR, 'Falha ao listar relatórios por status: ' . $e->getMessage(), null, $e );
        }
    }

}
