<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OperationStatus;
use App\Models\InvoiceStatus;
use App\Repositories\InvoiceStatusRepository;
use App\Services\Abstracts\BaseNoTenantService;
use App\Support\ServiceResult;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Serviço para gerenciamento de status de faturas.
 *
 * Este serviço gerencia operações CRUD para status de faturas, que são entidades globais
 * (sem tenant isolation) utilizadas como tabela de lookup por todos os tenants do sistema.
 * O serviço migra funcionalidades do legacy InvoiceStatusesService implementando
 * operações CRUD completas através do padrão BaseNoTenantService.
 *
 * Funcionalidades principais:
 * - Operações CRUD básicas (criar, ler, atualizar, deletar)
 * - Validação robusta de dados de entrada
 * - Busca por slug, nome e outros critérios
 * - Filtragem por status ativo/inativo
 * - Ordenação personalizada
 * - Gerenciamento de índice de ordem
 *
 * @package App\Services
 */
class InvoiceStatusService extends AbstractBaseService
{

}
