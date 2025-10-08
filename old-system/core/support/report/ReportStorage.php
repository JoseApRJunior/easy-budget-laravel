<?php

namespace core\support\report;

class ReportStorage
{
    private $config;
    private $baseDir;
    private $allowedFormats = [ 'pdf', 'csv', 'xlsx' ];

    public function __construct()
    {
        // Carrega configuração
        $this->config = require BASE_PATH . '/config/report.php';

        // Define propriedades
        $this->baseDir = $this->config[ 'reports' ][ 'base_dir' ];
        $this->allowedFormats = $this->config[ 'reports' ][ 'allowed_formats' ];

        // Garante que o diretório existe
        if (!file_exists($this->baseDir)) {
            mkdir($this->baseDir, 0755, true);
        }
    }

    public function store($report, $content)
    {
        try {
            // Valida tamanho
            if (strlen($content) > $this->config[ 'reports' ][ 'max_size' ]) {
                throw new \RuntimeException('Arquivo muito grande');
            }

            // Valida formato
            if (!in_array($report[ 'format' ], $this->allowedFormats)) {
                throw new \InvalidArgumentException('Formato inválido');
            }

            // Cria diretório para o tipo de relatório
            $directory = sprintf(
                '%s/%s/%s',
                $this->baseDir,
                $report[ 'type' ],
                date('Y/m'),
            );

            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }

            // Caminho completo
            $path = $directory . '/' . $report[ 'pdf_name' ];

            // Salva o arquivo
            if (file_put_contents($path, $content) === false) {
                throw new \RuntimeException('Erro ao salvar arquivo');
            }

            // Atualiza o registro do relatório
            $report[ 'file_path' ] = str_replace($this->baseDir . '/', '', $path);
            $report[ 'size' ] = strlen($content);

            return $report;

        } catch (\Exception $e) {
            // Log do erro
            error_log('Erro ao salvar relatório: ' . $e->getMessage());

            throw $e;
        }
    }

    public function get($path)
    {
        $fullPath = $this->baseDir . '/' . $path;

        if (!file_exists($fullPath)) {
            throw new \RuntimeException('Arquivo não encontrado');
        }

        return file_get_contents($fullPath);
    }

    public function delete($path)
    {
        $fullPath = $this->baseDir . '/' . $path;

        if (file_exists($fullPath)) {
            return unlink($fullPath);
        }

        return false;
    }

}
