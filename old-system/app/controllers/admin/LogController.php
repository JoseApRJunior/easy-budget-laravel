<?php

namespace app\controllers\admin;

use app\controllers\AbstractController;
use core\library\Response;
use core\library\Twig;
use http\Request;

class LogController extends AbstractController
{
    public function __construct(
        private Twig $twig,
        Request $request,
    ) {
        parent::__construct($request);
    }

    public function index(): Response
    {
        $logDir = STORAGE_PATH . '/logs/';
        // Pega a data da requisição ou usa a data atual como padrão
        $selectedDate = $this->request->get('date') ?? date('Y-m-d');

        $logFileName = "app-$selectedDate.log";
        $logFilePath = "$logDir$logFileName";

        $logs = [];
        if (file_exists($logFilePath)) {
            $fileContent = file_get_contents($logFilePath);
            $logEntries = preg_split('/(?=\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\])/', $fileContent, -1, PREG_SPLIT_NO_EMPTY);

            foreach ($logEntries as $entry) {
                $logs[] = $this->parseLogEntry($entry);
            }
            $logs = array_reverse($logs);
        }

        // Lista todos os arquivos de log disponíveis para o seletor
        $availableLogs = glob("{$logDir}app-*.log");
        $logDates = array_map(function ($file) {
            preg_match('/app-(\d{4}-\d{2}-\d{2})\.log/', $file, $matches);

            return $matches[ 1 ] ?? null;
        }, $availableLogs);

        // Remove nulos e ordena as datas em ordem decrescente
        $logDates = array_filter($logDates);
        rsort($logDates);

        return new Response($this->twig->env->render('pages/admin/logs/index.twig', [
            'logs' => $logs,
            'logDates' => $logDates,
            'selectedDate' => $selectedDate,
        ]));
    }

    /**
     * Parses a single log entry string into a structured array.
     *
     * @param string $entry The raw log entry.
     * @return array The parsed log data.
     */
    private function parseLogEntry(string $entry): array
    {
        $entry = trim($entry);
        if (preg_match('/^\[(.*?)\] \w+\.(.*?): (.*)/s', $entry, $matches)) {
            $fullContent = trim($matches[ 3 ]);

            $log_item = [
                'datetime' => $matches[ 1 ],
                'level' => trim($matches[ 2 ]),
                'message' => 'Não foi possível extrair a mensagem.',
                'details' => $fullContent,
                'is_html' => false,
            ];

            if (str_starts_with($fullContent, '<!DOCTYPE html>')) {
                $log_item[ 'is_html' ] = true;
                // Regex corrigido: removido espaço em `</span>`
                if (
                    preg_match(
                        "/<strong>Mensagem:<\/strong>.*?<span.*?>(.*?)<\ /span>/s",
                        $fullContent,
                        $messageMatch,
                    )
                ) {
                    $log_item[ 'message' ] = html_entity_decode(trim($messageMatch[ 1 ]));
                } else {
                    $log_item[ 'message' ] = 'Erro Detalhado (Visualização HTML)';
                }
            } else {
                // Divide a mensagem no "Stack Trace:" para uma melhor visualização
                $parts = preg_split('/(\R*Stack Trace:)/', $fullContent, 2, PREG_SPLIT_DELIM_CAPTURE);
                $log_item[ 'message' ] = trim($parts[ 0 ]);

                // Remonta o "Stack Trace:" com o restante dos detalhes
                $log_item[ 'details' ] = isset($parts[ 1 ]) ? trim($parts[ 1 ] . ($parts[ 2 ] ?? '')) : '';
            }

            return $log_item;
        }

        // Fallback para entradas que não correspondem ao padrão
        return [
            'datetime' => 'N/A',
            'level' => 'UNKNOWN',
            'message' => $entry,
            'details' => '',
            'is_html' => false,
        ];
    }

    /**
     * @inheritDoc
     */
    public function activityLogger(
        int $tenant_id,
        int $user_id,
        string $action_type,
        string $entity_type,
        int
        $entity_id,
        string $description,
        array $metadata = [],
    ) {
    }

}
