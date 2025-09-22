<?php

declare(strict_types=1);

namespace core\services;

/**
 * Serviço de Monitoramento de Segurança
 * 
 * Responsável por monitorar atividades suspeitas, registrar logs de segurança
 * e gerar alertas baseados nas configurações definidas.
 */
class SecurityMonitoringService
{
    private array $config;
    private string $logFile;
    private array $requestCache = [];
    private array $failedAttempts = [];

    public function __construct()
    {
        $this->config = require __DIR__ . '/../../config/security_monitoring.php';
        $this->logFile = $this->config['logging']['log_file'];
        $this->ensureLogDirectory();
    }

    /**
     * Registra tentativa de autenticação
     * 
     * @param string $username Nome do usuário
     * @param bool $success Se a tentativa foi bem-sucedida
     * @param string $ip Endereço IP
     * @param array $additionalData Dados adicionais
     * @return void
     */
    public function logAuthenticationAttempt(string $username, bool $success, string $ip, array $additionalData = []): void
    {
        if (!$this->config['authentication']['log_attempts']) {
            return;
        }

        // Log apenas falhas ou sucessos baseado na configuração
        if (!$success && !$this->config['authentication']['log_failures']) {
            return;
        }
        if ($success && !$this->config['authentication']['log_successes']) {
            return;
        }

        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => 'authentication',
            'username' => $username,
            'success' => $success,
            'ip' => $ip,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'additional_data' => $additionalData
        ];

        $this->writeLog($logData);

        // Verifica tentativas de força bruta
        if (!$success) {
            $this->trackFailedAttempt($username, $ip);
        }
    }

    /**
     * Registra execução de middleware
     * 
     * @param string $middlewareName Nome do middleware
     * @param float $executionTime Tempo de execução
     * @param bool $success Se a execução foi bem-sucedida
     * @param array $additionalData Dados adicionais
     * @return void
     */
    public function logMiddlewareExecution(string $middlewareName, float $executionTime, bool $success, array $additionalData = []): void
    {
        if (!$this->config['middleware']['log_execution']) {
            return;
        }

        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => 'middleware',
            'middleware' => $middlewareName,
            'execution_time' => $executionTime,
            'success' => $success,
            'memory_usage' => memory_get_usage(true),
            'additional_data' => $additionalData
        ];

        $this->writeLog($logData);

        // Verifica performance
        if ($executionTime > $this->config['middleware']['performance_threshold']) {
            $this->alertSlowMiddleware($middlewareName, $executionTime);
        }
    }

    /**
     * Registra acesso a rota
     * 
     * @param string $method Método HTTP
     * @param string $uri URI da requisição
     * @param int $statusCode Código de status da resposta
     * @param string $ip Endereço IP
     * @return void
     */
    public function logRouteAccess(string $method, string $uri, int $statusCode, string $ip): void
    {
        // Log 404 errors
        if ($statusCode === 404 && $this->config['routes']['log_404_errors']) {
            $this->log404Error($method, $uri, $ip);
        }

        // Log critical routes
        if ($this->isCriticalRoute($uri) && $this->config['routes']['log_critical_routes']) {
            $this->logCriticalRouteAccess($method, $uri, $statusCode, $ip);
        }

        // Verifica padrões suspeitos
        if ($this->isSuspiciousPattern($uri)) {
            $this->logSuspiciousActivity('suspicious_route_pattern', [
                'method' => $method,
                'uri' => $uri,
                'ip' => $ip
            ]);
        }

        // Verifica rate limiting
        $this->checkRateLimit($ip);
    }

    /**
     * Registra atividade suspeita
     * 
     * @param string $type Tipo de atividade suspeita
     * @param array $data Dados da atividade
     * @return void
     */
    public function logSuspiciousActivity(string $type, array $data): void
    {
        if (!$this->config['suspicious_activity']['enabled']) {
            return;
        }

        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => 'suspicious_activity',
            'activity_type' => $type,
            'severity' => $this->getSeverityLevel($type),
            'data' => $data
        ];

        $this->writeLog($logData);
        $this->sendAlert($type, $data);
    }

    /**
     * Registra mudança de dados sensíveis
     * 
     * @param string $table Nome da tabela
     * @param string $action Ação realizada
     * @param int $recordId ID do registro
     * @param array $changes Mudanças realizadas
     * @param int $userId ID do usuário
     * @return void
     */
    public function logDataChange(string $table, string $action, int $recordId, array $changes, int $userId): void
    {
        if (!$this->config['audit']['log_data_changes']) {
            return;
        }

        if (!in_array($table, $this->config['audit']['sensitive_tables'])) {
            return;
        }

        if (!in_array($action, $this->config['audit']['sensitive_actions'])) {
            return;
        }

        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => 'data_audit',
            'table' => $table,
            'action' => $action,
            'record_id' => $recordId,
            'user_id' => $userId,
            'changes' => $changes,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ];

        $this->writeLog($logData);
    }

    /**
     * Verifica se uma rota é crítica
     * 
     * @param string $uri URI da requisição
     * @return bool
     */
    private function isCriticalRoute(string $uri): bool
    {
        foreach ($this->config['routes']['critical_routes'] as $pattern) {
            if (fnmatch($pattern, $uri)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Verifica se uma URI contém padrões suspeitos
     * 
     * @param string $uri URI da requisição
     * @return bool
     */
    private function isSuspiciousPattern(string $uri): bool
    {
        foreach ($this->config['routes']['suspicious_patterns'] as $pattern) {
            if (strpos($uri, $pattern) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Verifica rate limiting por IP
     * 
     * @param string $ip Endereço IP
     * @return void
     */
    private function checkRateLimit(string $ip): void
    {
        $currentMinute = date('Y-m-d H:i');
        $key = $ip . '_' . $currentMinute;

        if (!isset($this->requestCache[$key])) {
            $this->requestCache[$key] = 0;
        }

        $this->requestCache[$key]++;

        if ($this->requestCache[$key] > $this->config['suspicious_activity']['max_requests_per_minute']) {
            $this->logSuspiciousActivity('rate_limit_exceeded', [
                'ip' => $ip,
                'requests_count' => $this->requestCache[$key],
                'time_window' => $currentMinute
            ]);
        }
    }

    /**
     * Rastreia tentativas de login falhadas
     * 
     * @param string $username Nome do usuário
     * @param string $ip Endereço IP
     * @return void
     */
    private function trackFailedAttempt(string $username, string $ip): void
    {
        $key = $username . '_' . $ip;
        $currentHour = date('Y-m-d H');

        if (!isset($this->failedAttempts[$key])) {
            $this->failedAttempts[$key] = [];
        }

        $this->failedAttempts[$key][] = time();

        // Remove tentativas antigas (mais de 1 hora)
        $this->failedAttempts[$key] = array_filter(
            $this->failedAttempts[$key],
            fn($timestamp) => $timestamp > (time() - 3600)
        );

        // Verifica se excedeu o limite
        if (count($this->failedAttempts[$key]) >= $this->config['authentication']['failed_attempts_threshold']) {
            $this->logSuspiciousActivity('brute_force_attack', [
                'username' => $username,
                'ip' => $ip,
                'attempts_count' => count($this->failedAttempts[$key])
            ]);
        }
    }

    /**
     * Registra erro 404
     * 
     * @param string $method Método HTTP
     * @param string $uri URI da requisição
     * @param string $ip Endereço IP
     * @return void
     */
    private function log404Error(string $method, string $uri, string $ip): void
    {
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => '404_error',
            'method' => $method,
            'uri' => $uri,
            'ip' => $ip,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'referer' => $_SERVER['HTTP_REFERER'] ?? 'unknown'
        ];

        $this->writeLog($logData);
    }

    /**
     * Registra acesso a rota crítica
     * 
     * @param string $method Método HTTP
     * @param string $uri URI da requisição
     * @param int $statusCode Código de status
     * @param string $ip Endereço IP
     * @return void
     */
    private function logCriticalRouteAccess(string $method, string $uri, int $statusCode, string $ip): void
    {
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => 'critical_route_access',
            'method' => $method,
            'uri' => $uri,
            'status_code' => $statusCode,
            'ip' => $ip,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ];

        $this->writeLog($logData);
    }

    /**
     * Envia alerta para middleware lento
     * 
     * @param string $middlewareName Nome do middleware
     * @param float $executionTime Tempo de execução
     * @return void
     */
    private function alertSlowMiddleware(string $middlewareName, float $executionTime): void
    {
        if ($this->config['middleware']['alert_on_slow_middleware']) {
            $this->sendAlert('slow_middleware', [
                'middleware' => $middlewareName,
                'execution_time' => $executionTime,
                'threshold' => $this->config['middleware']['performance_threshold']
            ]);
        }
    }

    /**
     * Obtém o nível de severidade para um tipo de atividade
     * 
     * @param string $type Tipo de atividade
     * @return string
     */
    private function getSeverityLevel(string $type): string
    {
        $severityMap = [
            'brute_force_attack' => 'high',
            'suspicious_route_pattern' => 'medium',
            'rate_limit_exceeded' => 'medium',
            'unauthorized_access' => 'high',
            'slow_middleware' => 'low'
        ];

        return $severityMap[$type] ?? 'medium';
    }

    /**
     * Envia alerta baseado no tipo
     * 
     * @param string $type Tipo de alerta
     * @param array $data Dados do alerta
     * @return void
     */
    private function sendAlert(string $type, array $data): void
    {
        if (!$this->config['alerts']['enabled']) {
            return;
        }

        if (!in_array($type, $this->config['alerts']['alert_types'])) {
            return;
        }

        // Log do alerta
        $alertData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => 'security_alert',
            'alert_type' => $type,
            'severity' => $this->getSeverityLevel($type),
            'data' => $data
        ];

        $this->writeLog($alertData);

        // Aqui seria implementado o envio de email ou webhook
        // Por enquanto, apenas registra no log
    }

    /**
     * Escreve log no arquivo
     * 
     * @param array $data Dados a serem registrados
     * @return void
     */
    private function writeLog(array $data): void
    {
        if (!$this->config['logging']['enabled']) {
            return;
        }

        $logEntry = json_encode($data) . "\n";
        file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);

        // Verifica se precisa rotacionar o log
        $this->rotateLogIfNeeded();
    }

    /**
     * Garante que o diretório de logs existe
     * 
     * @return void
     */
    private function ensureLogDirectory(): void
    {
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }

    /**
     * Rotaciona o arquivo de log se necessário
     * 
     * @return void
     */
    private function rotateLogIfNeeded(): void
    {
        if (!$this->config['logging']['rotate_logs']) {
            return;
        }

        if (!file_exists($this->logFile)) {
            return;
        }

        $fileSize = filesize($this->logFile);
        if ($fileSize > $this->config['logging']['max_file_size']) {
            $rotatedFile = $this->logFile . '.' . date('Y-m-d-H-i-s');
            rename($this->logFile, $rotatedFile);

            // Comprime o arquivo rotacionado
            if (function_exists('gzencode')) {
                $content = file_get_contents($rotatedFile);
                file_put_contents($rotatedFile . '.gz', gzencode($content));
                unlink($rotatedFile);
            }
        }
    }

    /**
     * Obtém estatísticas de segurança
     * 
     * @return array
     */
    public function getSecurityStats(): array
    {
        // Implementação básica - pode ser expandida
        return [
            'total_logs' => $this->countLogEntries(),
            'failed_logins_today' => $this->countFailedLoginsToday(),
            'suspicious_activities_today' => $this->countSuspiciousActivitiesToday(),
            'critical_route_accesses_today' => $this->countCriticalRouteAccessesToday()
        ];
    }

    /**
     * Conta entradas de log
     * 
     * @return int
     */
    private function countLogEntries(): int
    {
        if (!file_exists($this->logFile)) {
            return 0;
        }
        return count(file($this->logFile));
    }

    /**
     * Conta logins falhados hoje
     * 
     * @return int
     */
    private function countFailedLoginsToday(): int
    {
        // Implementação simplificada
        return 0;
    }

    /**
     * Conta atividades suspeitas hoje
     * 
     * @return int
     */
    private function countSuspiciousActivitiesToday(): int
    {
        // Implementação simplificada
        return 0;
    }

    /**
     * Conta acessos a rotas críticas hoje
     * 
     * @return int
     */
    private function countCriticalRouteAccessesToday(): int
    {
        // Implementação simplificada
        return 0;
    }
}