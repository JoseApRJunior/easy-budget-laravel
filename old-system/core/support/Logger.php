<?php

namespace core\support;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger as MonologLogger;

class Logger
{
    private MonologLogger $logger;

    public function __construct()
    {
        // Criar diretório de logs se não existir
        $logPath = STORAGE_PATH . '/logs';
        if (!is_dir($logPath)) {
            mkdir($logPath, 0755, true);
        }

        // Criar instância do Monolog
        $this->logger = new MonologLogger('app');

        // Formato personalizado
        $dateFormat = "Y-m-d H:i:s";
        $output = "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n";
        $formatter = new LineFormatter($output, $dateFormat, true);

        // Handler para arquivo diário
        $filename = $logPath . '/app-' . date('Y-m-d') . '.log';
        $fileHandler = new StreamHandler($filename, Level::Debug);
        $fileHandler->setFormatter($formatter);

        // Handler para stdout (desenvolvimento)
        if (env('APP_ENV') === 'development') {
            $streamHandler = new StreamHandler('php://stdout', Level::Debug);
            $streamHandler->setFormatter($formatter);
            $this->logger->pushHandler($streamHandler);
        }

        $this->logger->pushHandler($fileHandler);
    }

    public function info(string $message, array $context = []): void
    {
        $this->logger->info($message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->logger->error($message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->logger->warning($message, $context);
    }

    public function debug(string $message, array $context = []): void
    {
        $this->logger->debug($message, $context);
    }

}
