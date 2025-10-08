<?php

namespace core\support;

use http\Redirect;
use PDOException;
use Throwable;

class ErrorHandler
{
    public function handle(Throwable $e)
    {
        // 1. Sempre logar o erro em formato de texto, independente do ambiente.
        if (env('APP_ENV') === 'production') {
            // Log apenas essencial
            logger()->error($e->getMessage());
        } else {
            // Log detalhado apenas em dev
            logger()->error($this->formatErrorAsText($e));
        }

        // 2. Decidir o que mostrar para o usuário.
        if (env('APP_ENV') === 'production') {
            // Em produção, redireciona para uma página de erro genérica.
            Redirect::redirect('/internal-error')->send();
        } else {
            // Em desenvolvimento, mostra a página de erro detalhada em HTML.
            $this->displayDevelopmentError($e);
        }
    }

    private function displayDevelopmentError(Throwable $e): void
    {
        http_response_code(500);
        // Limpa qualquer saída anterior
        ob_clean();

        // Inicia a captura do buffer de saída
        ob_start();

        // Define o cabeçalho para HTML
        header('Content-Type: text/html; charset=utf-8');

        // Início do HTML com estilo para fundo branco
        echo "<!DOCTYPE html><html><head><title>Erro Detalhado</title>";
        echo "<style>body{background-color:white;font-family:Arial,sans-serif;padding:20px;} pre{background-color:#F5F5F5;padding:15px;border-radius:5px;white-space:pre-wrap;word-wrap:break-word;}</style></head><body>";

        echo "<h1 style='color:#D32F2F;'>Erro Detalhado</h1>";
        echo "<pre>";
        echo "<strong>Tipo:</strong> " . get_class($e) . "\n";
        echo "  Mensagem: " . htmlspecialchars(trim($this->pregReplaceStackTrace($e->getMessage()))) . "\n";

        // Mensagem do erro
        $previous = $e->getPrevious();

        // Informações sobre a exceção anterior (se existir)
        if ($previous instanceof Throwable) {
            echo "<strong>Exceção Anterior:</strong>\n";
            $this->assembleMessage($previous);

            // Verifica se há uma segunda exceção anterior
            $secondPrevious = $previous->getPrevious();
            if ($secondPrevious instanceof Throwable) {
                echo "<strong>Segunda Exceção Anterior:</strong>\n";
                $this->assembleMessage($secondPrevious);

                // Verifica se há uma terceira exceção anterior
                $thirdPrevious = $secondPrevious->getPrevious();
                if ($thirdPrevious instanceof Throwable) {
                    echo "<strong>Terceira Exceção Anterior:</strong>\n";
                    $this->assembleMessage($thirdPrevious);
                }
            }
        }

        if ($e instanceof PDOException || ($previous instanceof PDOException)) {
            $pdoException = $e instanceof PDOException ? $e : $previous;
            preg_match('/SQLSTATE\[.*\]: (.*) in /', $pdoException->getMessage(), $matches);
            if (!empty($matches[ 1 ])) {
                echo "<strong>Erro SQL:</strong> <span style='color:#1976D2;'>" . htmlspecialchars($matches[ 1 ]) . "</span>\n\n";
            }
        } else {
            $message = $e->getMessage();
            echo "<strong>Mensagem:</strong> <span style='color:#D32F2F;'>" . htmlspecialchars($message) . "</span>\n\n";
        }

        // Arquivo e linha
        echo "<strong>Arquivo:</strong> " . $e->getFile() . "\n\n";
        echo "<strong>Linha:</strong> " . $e->getLine() . "\n\n";

        // Stack trace
        echo "<strong>Stack Trace:</strong>\n";
        $this->getTrace($e, 'html');
        echo "</pre>";
        echo "</body></html>";

        // Em desenvolvimento, exibe o erro e para a execução.
        echo ob_get_clean();
        exit;
    }

    /**
     * Formata a exceção como uma string de texto puro para ser salva no log.
     */
    private function formatErrorAsText(Throwable $e): string
    {
        $logMessage = "Tipo: " . get_class($e) . "\n";
        $logMessage .= "Mensagem: " . trim($this->pregReplaceStackTrace($e->getMessage())) . "\n";
        $logMessage .= "Arquivo: " . $e->getFile() . "\n";
        $logMessage .= "Linha: " . $e->getLine() . "\n\n";
        $logMessage .= "Stack Trace:\n";

        $previous = $e->getPrevious();
        if ($previous instanceof Throwable) {
            $logMessage .= "--- Exceção Anterior ---\n";
            $logMessage .= $this->formatErrorAsText($previous);
            $logMessage .= "--- Fim da Exceção Anterior ---\n\n";
        }

        // Capture the output of getTrace into a string
        ob_start();
        $this->getTrace($e, 'text');
        $logMessage .= ob_get_clean();

        return $logMessage;
    }

    public function pregReplaceStackTrace(string $message)
    {
        return preg_replace('/Stack trace:[\s\S]*/', '', $message);
    }

    public function assembleMessage(Throwable $previous)
    {
        echo "  Tipo: " . get_class($previous) . "\n";
        echo "  Mensagem: " . htmlspecialchars(trim($this->pregReplaceStackTrace($previous->getMessage()))) . "\n";
        $this->getTrace($previous, 'html');
        echo "  Arquivo: " . $previous->getFile() . "\n";
        echo "  Linha: " . $previous->getLine() . "\n\n";
    }

    public function getTrace(Throwable $e, string $format = 'html')
    {
        $trace = $e->getTrace();
        foreach ($trace as $index => $t) {
            $file = $t[ 'file' ] ?? ($format === 'html' ? '<i>Arquivo interno do PHP</i>' : 'Arquivo interno do PHP');
            $line = $t[ 'line' ] ?? 'N/A';
            $class = $t[ 'class' ] ?? '';
            $type = $t[ 'type' ] ?? '';
            $function = $t[ 'function' ];

            // Formata os argumentos para exibição
            $args = [];
            if (!empty($t[ 'args' ])) {
                foreach ($t[ 'args' ] as $arg) {
                    if (is_object($arg)) {
                        $args[] = 'Object(' . get_class($arg) . ')';
                    } elseif (is_array($arg)) {
                        $args[] = 'Array[' . count($arg) . ']';
                    } else {
                        $arg_str = strval($arg);
                        $args[] = $format === 'html' ? htmlspecialchars($arg_str) : $arg_str;
                    }
                }
            }

            echo "#{$index} {$file}({$line}): {$class}{$type}{$function}(" . implode(', ', $args) . ")\n";
        }
    }

}
