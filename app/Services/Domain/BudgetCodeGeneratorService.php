<?php

declare(strict_types=1);

namespace App\Services\Domain;

use App\Repositories\BudgetRepository;
use App\Support\ServiceResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Serviço para geração de códigos únicos de orçamentos.
 * Retorna ServiceResult para consistência com a arquitetura estabelecida.
 *
 * Substitui o método legacy `generateNextCode()` usando algoritmos otimizados
 * com controle de concorrência via database locks para evitar duplicatas.
 */
class BudgetCodeGeneratorService
{
    /**
     * Prefixo para códigos de orçamento.
     */
    private const CODE_PREFIX = 'ORC';

    /**
     * Comprimento mínimo do código.
     */
    private const MIN_CODE_LENGTH = 8;

    /**
     * Comprimento máximo do código.
     */
    private const MAX_CODE_LENGTH = 20;

    /**
     * Duração do lock em segundos.
     */
    private const LOCK_TIMEOUT = 10;

    public function __construct(
        private BudgetRepository $budgetRepository,
    ) {}

    /**
     * Gera um código único para orçamento dentro do tenant.
     *
     * Usa database locks para garantir concorrência segura e evitar duplicatas.
     * Padrão: ORC-2025-11-000001 (ORC-ANO-MES-SEQUENCIAL)
     *
     * @param  string|null  $prefix  Prefixo customizado (padrão: ORC)
     * @return ServiceResult<string> Resultado da operação com código gerado
     */
    public function generateUniqueCode(?string $prefix = null): ServiceResult
    {
        $prefix = $prefix ?? self::CODE_PREFIX;
        $tenantId = auth()->user()->tenant_id ?? 0;
        $maxAttempts = 5;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $code = DB::connection()->transaction(function () use ($tenantId, $prefix) {
                    // Adquire lock exclusivo para evitar race conditions
                    $this->acquireLock($tenantId, $prefix);

                    // Gera o próximo código
                    $code = $this->generateNextCode($prefix);

                    // Verifica se o código já existe
                    if ($this->codeExists($code)) {
                        throw new \Exception("Código já existe: {$code}");
                    }

                    return $code;
                });

                return ServiceResult::success($code, 'Código único gerado com sucesso');
            } catch (\Exception $e) {
                if ($attempt === $maxAttempts) {
                    return ServiceResult::error(
                        "Falha ao gerar código único após {$maxAttempts} tentativas: ".$e->getMessage()
                    );
                }

                // Aguarda um tempo aleatório antes da próxima tentativa
                usleep(rand(100000, 500000)); // 0.1-0.5 segundos
            }
        }

        return ServiceResult::error('Falha crítica na geração de código único');
    }

    /**
     * Gera código sequencial baseado na data atual.
     *
     * @param  string  $prefix  Prefixo do código
     * @return string Código sequencial
     */
    private function generateNextCode(string $prefix): string
    {
        $year = date('Y');
        $month = date('m');

        // Busca último código do mês atual
        $lastBudget = $this->budgetRepository->getLastBudgetByMonth($year, $month);

        $sequential = 1;
        if ($lastBudget) {
            $sequential = $this->extractSequentialNumber($lastBudget->code) + 1;
        }

        return sprintf(
            '%s-%s-%s-%06d',
            $prefix,
            $year,
            $month,
            $sequential,
        );
    }

    /**
     * Extrai o número sequencial de um código.
     *
     * @param  string  $code  Código completo (ex: ORC-2025-11-000001)
     * @return int Número sequencial
     */
    private function extractSequentialNumber(string $code): int
    {
        $parts = explode('-', $code);

        if (count($parts) >= 4) {
            return (int) $parts[3];
        }

        return 1;
    }

    /**
     * Verifica se um código já existe no tenant.
     *
     * @param  string  $code  Código a verificar
     * @return bool True se existir
     */
    private function codeExists(string $code): bool
    {
        $existing = $this->budgetRepository->findByCode($code);

        return $existing !== null;
    }

    /**
     * Adquire lock exclusivo para geração de código.
     *
     * @param  int  $tenantId  ID do tenant
     * @param  string  $prefix  Prefixo
     *
     * @throws \Exception Se não conseguir adquirir lock
     */
    private function acquireLock(int $tenantId, string $prefix): void
    {
        $lockKey = "budget_code_gen:{$tenantId}:{$prefix}";

        // Tenta adquirir lock com timeout
        $acquired = DB::statement(
            'SELECT GET_LOCK(?, ?)',
            [$lockKey, self::LOCK_TIMEOUT],
        );

        if (! $acquired) {
            throw new \Exception('Não foi possível adquirir lock para geração de código');
        }
    }

    /**
     * Libera lock manualmente (normalmente feito automaticamente pelo DB).
     *
     * @param  int  $tenantId  ID do tenant
     * @param  string  $prefix  Prefixo
     */
    private function releaseLock(int $tenantId, string $prefix): void
    {
        $lockKey = "budget_code_gen:{$tenantId}:{$prefix}";
        DB::statement('SELECT RELEASE_LOCK(?)', [$lockKey]);
    }

    /**
     * Valida formato do código.
     *
     * @param  string  $code  Código a validar
     * @return bool True se válido
     */
    public function validateCodeFormat(string $code): bool
    {
        // Padrão: ORC-YYYY-MM-XXXXXX ou código customizado com letras e números
        $pattern = '/^[A-Z]{3}-[0-9]{4}-[0-9]{2}-[0-9]{6}$/';

        // Também aceita códigos customizados
        $customPattern = '/^[A-Z]{2,5}-[A-Z0-9-]+$/';

        return preg_match($pattern, $code) || preg_match($customPattern, $code);
    }

    /**
     * Extrai informações do código.
     *
     * @param  string  $code  Código completo
     * @return array|null Informações do código ou null se inválido
     */
    public function parseCode(string $code): ?array
    {
        if (! $this->validateCodeFormat($code)) {
            return null;
        }

        $parts = explode('-', $code);

        if (count($parts) >= 4) {
            return [
                'prefix' => $parts[0],
                'year' => (int) $parts[1],
                'month' => (int) $parts[2],
                'sequential' => (int) $parts[3],
                'full_code' => $code,
            ];
        }

        return [
            'prefix' => $parts[0] ?? '',
            'custom_format' => true,
            'full_code' => $code,
        ];
    }

    /**
     * Gera código com prefixo customizado.
     *
     * @param  string  $customPrefix  Prefixo customizado (2-5 caracteres)
     * @return ServiceResult<string> Resultado da operação com código gerado
     */
    public function generateWithCustomPrefix(string $customPrefix): ServiceResult
    {
        if (strlen($customPrefix) < 2 || strlen($customPrefix) > 5) {
            return ServiceResult::error('Prefixo deve ter entre 2 e 5 caracteres');
        }

        if (! preg_match('/^[A-Z0-9]+$/', $customPrefix)) {
            return ServiceResult::error('Prefixo deve conter apenas letras e números');
        }

        return $this->generateUniqueCode(strtoupper($customPrefix));
    }

    /**
     * Gera código aleatório para orçamento (uso interno/testes).
     *
     * @return ServiceResult<string> Resultado da operação com código gerado
     */
    public function generateRandomCode(): ServiceResult
    {
        do {
            $randomSuffix = strtoupper(Str::random(6));
            $code = sprintf('%s-%s', self::CODE_PREFIX, $randomSuffix);
        } while ($this->codeExists($code));

        return ServiceResult::success($code, 'Código aleatório gerado com sucesso');
    }
}
