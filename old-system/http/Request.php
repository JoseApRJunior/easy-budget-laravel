<?php

namespace http;

use core\library\Sanitize;
use InvalidArgumentException;

class Request
{
    private readonly Sanitize $sanitize;
    private readonly array    $input;
    private readonly array    $json;
    private readonly array    $files;

    private const SUPPORTED_PARAM_TYPES = [ 'string', 'int', 'float', 'bool', 'array' ];
    private const ALLOWED_FILE_TYPES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ];

    private const MAX_FILE_SIZE = 10485760; // 10MB em bytes

    public function __construct(
        public readonly array $get,
        public readonly array $post,
        public readonly array $server,
        private readonly array $rawFiles,
        public readonly array $cookie,
    ) {
        $this->sanitize = new Sanitize();

        // Process all input sources once upon creation
        $this->json = $this->processJsonBody();
        $this->files = $this->processUploadedFiles();

        // Merge all input sources into a single `input` array
        // The order determines precedence: POST > JSON > GET
        $this->input = array_merge(
            $this->sanitize->execute($this->get),
            $this->json,
            $this->sanitize->execute($this->post),
        );
    }

    public static function create(): self
    {
        return new self($_GET, $_POST, $_SERVER, $_FILES, $_COOKIE);
    }

    private function processJsonBody(): array
    {
        if (str_contains($this->server[ 'CONTENT_TYPE' ] ?? '', 'application/json')) {
            try {
                $jsonData = file_get_contents('php://input');
                $data = empty($jsonData) ? [] : json_decode($jsonData, true, 512, JSON_THROW_ON_ERROR);

                return $this->sanitize->execute($data);
            } catch (\JsonException $e) {
                // In case of malformed JSON, return an empty array or handle as an error
                return [];
            }
        }

        return [];
    }

    private function processUploadedFiles(): array
    {
        if (empty($this->rawFiles)) {
            return [];
        }

        $processedFiles = [];

        foreach ($this->rawFiles as $inputName => $fileInfo) {
            // Verifica se é um array de arquivos
            if (is_array($fileInfo[ 'name' ])) {
                $processedFiles[ $inputName ] = $this->processMultipleFiles($fileInfo);
            } else {
                $processedFiles[ $inputName ] = $this->processSingleFile($fileInfo);
            }
        }

        return $processedFiles;
    }

    private function processSingleFile(array $fileInfo): array
    {
        // Retorna null se não houver arquivo ou ocorrer erro
        if ($fileInfo[ 'error' ] === UPLOAD_ERR_NO_FILE) {
            return [];
        }

        return [
            'name' => $this->sanitizeFileName($fileInfo[ 'name' ]),
            'type' => $fileInfo[ 'type' ],
            'tmp_name' => $fileInfo[ 'tmp_name' ],
            'error' => $fileInfo[ 'error' ],
            'size' => $fileInfo[ 'size' ],
            'extension' => $this->getFileExtension($fileInfo[ 'name' ]),
            'is_valid' => $this->validateFileUpload($fileInfo),
            'error_message' => $this->getFileErrorMessage($fileInfo[ 'error' ]),
        ];
    }

    private function processMultipleFiles(array $fileInfo): array
    {
        $files = [];
        $fileCount = count($fileInfo[ 'name' ]);

        for ($i = 0; $i < $fileCount; $i++) {
            // Pula arquivos não enviados
            if ($fileInfo[ 'error' ][ $i ] === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            $currentFile = [
                'name' => $fileInfo[ 'name' ][ $i ],
                'type' => $fileInfo[ 'type' ][ $i ],
                'tmp_name' => $fileInfo[ 'tmp_name' ][ $i ],
                'error' => $fileInfo[ 'error' ][ $i ],
                'size' => $fileInfo[ 'size' ][ $i ],
            ];

            $files[] = [
                'name' => $this->sanitizeFileName($currentFile[ 'name' ]),
                'type' => $currentFile[ 'type' ],
                'tmp_name' => $currentFile[ 'tmp_name' ],
                'error' => $currentFile[ 'error' ],
                'size' => $currentFile[ 'size' ],
                'extension' => $this->getFileExtension($currentFile[ 'name' ]),
                'is_valid' => $this->validateFileUpload($currentFile),
                'error_message' => $this->getFileErrorMessage($currentFile[ 'error' ]),
            ];
        }

        return $files;
    }

    private function validateFileUpload(array $fileInfo): bool
    {
        // Verifica erros de upload
        if ($fileInfo[ 'error' ] !== UPLOAD_ERR_OK) {
            return false;
        }

        // Verifica tamanho
        if ($fileInfo[ 'size' ] > self::MAX_FILE_SIZE) {
            return false;
        }

        // Verifica tipo de arquivo
        if (!empty($fileInfo[ 'type' ]) && !in_array($fileInfo[ 'type' ], self::ALLOWED_FILE_TYPES, true)) {
            return false;
        }

        // Verifica se é um arquivo real
        if (!is_uploaded_file($fileInfo[ 'tmp_name' ])) {
            return false;
        }

        return true;
    }

    private function getFileErrorMessage(int $errorCode): ?string
    {
        return match ($errorCode) {
            UPLOAD_ERR_OK => null,
            UPLOAD_ERR_INI_SIZE => 'O arquivo excede o tamanho máximo permitido pelo PHP',
            UPLOAD_ERR_FORM_SIZE => 'O arquivo excede o tamanho máximo permitido pelo formulário',
            UPLOAD_ERR_PARTIAL => 'O upload do arquivo foi feito parcialmente',
            UPLOAD_ERR_NO_FILE => 'Nenhum arquivo foi enviado',
            UPLOAD_ERR_NO_TMP_DIR => 'Pasta temporária ausente',
            UPLOAD_ERR_CANT_WRITE => 'Falha ao gravar arquivo em disco',
            UPLOAD_ERR_EXTENSION => 'Uma extensão PHP interrompeu o upload do arquivo',
            default => 'Erro desconhecido no upload do arquivo',
        };
    }

    private function sanitizeFileName(string $fileName): string
    {
        // Remove caracteres especiais e espaços
        $fileName = preg_replace('/[^a-zA-Z0-9\-._]/', '', $fileName);

        // Converte para minúsculas
        $fileName = strtolower($fileName);

        // Garante um nome único
        return uniqid('file_') . '_' . $fileName;
    }

    private function getFileExtension(string $fileName): string
    {
        return strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    }

    public function moveUploadedFile(array $fileInfo, string $destination): bool
    {
        // Verifica se o arquivo é válido
        if (!$this->validateFileUpload($fileInfo)) {
            return false;
        }

        // Cria o diretório se não existir
        $uploadDir = dirname($destination);
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                return false;
            }
        }

        // Move o arquivo
        return move_uploaded_file($fileInfo[ 'tmp_name' ], $destination);
    }

    // Métodos auxiliares para arquivos
    public function hasFile(string $key): bool
    {
        return isset($this->files[ $key ]) && !empty($this->files[ $key ]);
    }

    public function file(string $key): ?array
    {
        return $this->files[ $key ] ?? null;
    }

    public function allFiles(): array
    {
        return $this->files;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->input);
    }

    public function all(): array
    {
        return $this->input;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->input[ $key ] ?? $default;
    }

    public function get(string $key, string $type = 'string', mixed $default = null): mixed
    {
        if (!in_array($type, self::SUPPORTED_PARAM_TYPES, true)) {
            throw new InvalidArgumentException("Tipo de parâmetro inválido: {$type}");
        }

        $value = $this->input($key, $default);

        if ($value === null) {
            return $default;
        }

        return $this->sanitize->sanitizeParamValue($value, $type);
    }

    public function only(array $keys): array
    {
        return array_intersect_key($this->all(), array_flip($keys));
    }

    public function except(array $keys): array
    {
        return array_diff_key($this->all(), array_flip($keys));
    }

    public function getHost(): string
    {
        return $this->server[ 'HTTP_HOST' ]
            ?? $this->server[ 'SERVER_NAME' ]
            ?? '';
    }

    public function isSecure(): bool
    {
        return isset($this->server[ 'HTTPS' ]) && $this->server[ 'HTTPS' ] !== 'off';
    }

    public function getMethod(): string
    {
        return strtoupper($this->server[ 'REQUEST_METHOD' ] ?? 'GET');
    }

    public function isMethod(string $method): bool
    {
        return $this->getMethod() === strtoupper($method);
    }

    public function getContentType(): string
    {
        return $this->server[ 'CONTENT_TYPE' ] ?? '';
    }

}
