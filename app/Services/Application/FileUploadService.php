<?php

declare(strict_types=1);

namespace App\Services\Application;

use App\Models\SystemSettings;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

/**
 * Serviço para upload e manipulação de arquivos
 */
class FileUploadService
{
    private ImageManager $imageManager;

    public function __construct()
    {
        $this->imageManager = new ImageManager(Driver::class);
    }

    /**
     * Upload de avatar do usuário
     */
    public function uploadAvatar(UploadedFile $file, int $userId, int $tenantId): array
    {
        // Validações de segurança
        $this->validateFile($file, 'avatar');

        // Gera nome único para o arquivo
        $filename = $this->generateUniqueFilename($file, 'avatar', $userId);

        // Cria diferentes tamanhos do avatar
        $paths = [];

        try {
            // Upload do arquivo original
            $originalPath = $file->storeAs("avatars/{$tenantId}", $filename, 'public');
            $paths['original'] = $originalPath;

            // Cria thumbnails
            $paths['thumb'] = $this->createAvatarThumbnail($file, $filename, $tenantId, 150);
            $paths['medium'] = $this->createAvatarThumbnail($file, $filename, $tenantId, 300);

            return [
                'success' => true,
                'paths' => $paths,
                'filename' => $filename,
                'url' => asset('storage/'.$originalPath),
            ];

        } catch (Exception $e) {
            // Remove arquivos criados em caso de erro
            foreach ($paths as $path) {
                Storage::disk('public')->delete($path);
            }

            throw new Exception('Erro ao fazer upload do avatar: '.$e->getMessage());
        }
    }

    /**
     * Upload de logo da empresa
     */
    public function uploadCompanyLogo(UploadedFile $file, int $tenantId): array
    {
        // Validações de segurança
        $this->validateFile($file, 'logo');

        // Gera nome único para o arquivo
        $filename = $this->generateUniqueFilename($file, 'logo', $tenantId);

        $paths = [];

        try {
            // Upload do arquivo original
            $originalPath = $file->storeAs("logos/{$tenantId}", $filename, 'public');
            $paths['original'] = $originalPath;

            // Cria versões otimizadas
            $paths['thumb'] = $this->createLogoThumbnail($file, $filename, $tenantId, 200);
            $paths['medium'] = $this->createLogoThumbnail($file, $filename, $tenantId, 400);

            return [
                'success' => true,
                'paths' => $paths,
                'filename' => $filename,
                'url' => asset('storage/'.$originalPath),
            ];

        } catch (Exception $e) {
            // Remove arquivos criados em caso de erro
            foreach ($paths as $path) {
                Storage::disk('public')->delete($path);
            }

            throw new Exception('Erro ao fazer upload do logo: '.$e->getMessage());
        }
    }

    /**
     * Upload de arquivo genérico
     */
    public function uploadFile(UploadedFile $file, string $directory, int $tenantId, ?string $subfolder = null): array
    {
        // Validações básicas
        $this->validateFile($file, 'generic');

        // Gera nome único para o arquivo
        $filename = $this->generateUniqueFilename($file, 'file', $tenantId);

        $targetDirectory = $subfolder ? "{$directory}/{$tenantId}/{$subfolder}" : "{$directory}/{$tenantId}";

        try {
            $path = $file->storeAs($targetDirectory, $filename, 'public');

            return [
                'success' => true,
                'path' => $path,
                'filename' => $filename,
                'url' => asset('storage/'.$path),
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
            ];

        } catch (Exception $e) {
            throw new Exception('Erro ao fazer upload do arquivo: '.$e->getMessage());
        }
    }

    public function uploadImageWithProcessing(UploadedFile $file, int $tenantId, array $options = []): array
    {
        $this->validateFile($file, 'generic');
        $filename = $this->generateUniqueFilename($file, 'image', $tenantId);
        $directory = "uploads/{$tenantId}";

        $originalPath = $file->storeAs($directory, $filename, 'public');
        $targetPath = "{$directory}/processed_{$filename}";

        $image = $this->imageManager->read(Storage::disk('public')->path($originalPath));

        if (isset($options['resize'])) {
            $resize = $options['resize'];
            $width = $resize['width'] ?? null;
            $height = $resize['height'] ?? null;
            if ($width && $height) {
                $image->resize($width, $height);
            } elseif ($width) {
                $image->scale(width: $width);
            } elseif ($height) {
                $image->scale(height: $height);
            }
        }

        if (! empty($options['watermark']) && ! empty($options['watermark']['enabled'])) {
            $wm = $options['watermark'];
            $wmFile = $wm['file'] ?? Storage::disk('public')->path('watermarks/watermark.png');
            if (is_string($wmFile) && file_exists($wmFile)) {
                $position = $wm['position'] ?? 'top-right';
                $x = (int) ($wm['x'] ?? 10);
                $y = (int) ($wm['y'] ?? 10);
                $opacity = (int) ($wm['opacity'] ?? 70);
                $watermark = $this->imageManager->read($wmFile);
                if (isset($wm['width']) || isset($wm['height'])) {
                    $w = $wm['width'] ?? null;
                    $h = $wm['height'] ?? null;
                    if ($w && $h) {
                        $watermark->resize($w, $h);
                    } elseif ($w) {
                        $watermark->scale(width: $w);
                    } elseif ($h) {
                        $watermark->scale(height: $h);
                    }
                }
                $watermark->opacity($opacity);
                $image->place($watermark, $position, $x, $y);
            }
        }

        $image->toJpeg(quality: (int) ($options['quality'] ?? 90))->save(Storage::disk('public')->path($targetPath));

        return [
            'success' => true,
            'paths' => [
                'original' => $originalPath,
                'processed' => $targetPath,
            ],
            'url' => asset('storage/'.$targetPath),
            'filename' => $filename,
        ];
    }

    /**
     * Remove arquivo do storage
     */
    public function deleteFile(string $path): bool
    {
        try {
            return Storage::disk('public')->delete($path);
        } catch (Exception $e) {
            throw new Exception('Erro ao remover arquivo: '.$e->getMessage());
        }
    }

    /**
     * Validação de arquivo
     */
    private function validateFile(UploadedFile $file, string $type): void
    {
        // Verifica se houve erro no upload
        if (! $file->isValid()) {
            throw new Exception('Erro no upload do arquivo: '.$file->getErrorMessage());
        }

        // Obtém configurações do sistema para validação
        $systemSettings = SystemSettings::where('tenant_id', auth()->user()->tenant_id)->first();

        // Verificação de tamanho
        $maxSize = $systemSettings?->max_file_size ?? 2048; // KB
        if ($file->getSize() > $maxSize * 1024) {
            throw new Exception("Arquivo muito grande. Tamanho máximo permitido: {$maxSize}KB");
        }

        // Verificação de tipo MIME
        $allowedTypes = $this->getAllowedMimeTypes($type, $systemSettings);
        if (! in_array($file->getMimeType(), $allowedTypes)) {
            throw new Exception('Tipo de arquivo não permitido: '.$file->getMimeType());
        }

        // Verificação adicional para imagens
        if (str_starts_with($file->getMimeType(), 'image/')) {
            $this->validateImage($file);
        }
    }

    /**
     * Validação específica para imagens
     */
    private function validateImage(UploadedFile $file): void
    {
        // Verifica dimensões mínimas
        $imageSize = getimagesize($file->getPathname());
        if ($imageSize === false) {
            throw new Exception('Arquivo de imagem inválido');
        }

        [$width, $height] = $imageSize;

        // Dimensões mínimas
        if ($width < 50 || $height < 50) {
            throw new Exception('Imagem muito pequena. Dimensões mínimas: 50x50px');
        }

        // Dimensões máximas
        if ($width > 5000 || $height > 5000) {
            throw new Exception('Imagem muito grande. Dimensões máximas: 5000x5000px');
        }
    }

    /**
     * Tipos MIME permitidos por tipo de arquivo
     */
    private function getAllowedMimeTypes(string $type, ?SystemSettings $systemSettings): array
    {
        // Se o sistema tem tipos específicos configurados, usa eles
        if ($systemSettings && $systemSettings->allowed_file_types) {
            return $systemSettings->allowed_file_types;
        }

        // Caso contrário, usa tipos padrão por categoria
        return match ($type) {
            'avatar' => [
                'image/jpeg',
                'image/png',
                'image/gif',
                'image/webp',
            ],
            'logo' => [
                'image/jpeg',
                'image/png',
                'image/gif',
                'image/webp',
                'image/svg+xml',
            ],
            'generic' => [
                'image/jpeg',
                'image/png',
                'image/gif',
                'image/webp',
                'application/pdf',
                'text/plain',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ],
            default => [
                'image/jpeg',
                'image/png',
            ],
        };
    }

    /**
     * Gera nome único para arquivo
     */
    private function generateUniqueFilename(UploadedFile $file, string $prefix, int $identifier): string
    {
        $extension = $file->getClientOriginalExtension();
        $timestamp = now()->format('Y_m_d_H_i_s');
        $random = substr(md5(uniqid()), 0, 8);

        return "{$prefix}_{$identifier}_{$timestamp}_{$random}.{$extension}";
    }

    /**
     * Cria thumbnail para avatar
     */
    private function createAvatarThumbnail(UploadedFile $file, string $filename, int $tenantId, int $size): string
    {
        $originalPath = storage_path("app/public/avatars/{$tenantId}/{$filename}");
        $thumbPath = storage_path("app/public/avatars/{$tenantId}/thumb_{$size}_{$filename}");

        // Cria thumbnail quadrado
        $image = $this->imageManager->read($file->getPathname());
        $image->cover($size, $size);
        $image->toJpeg(quality: 90)->save($thumbPath); // 90% qualidade

        return "avatars/{$tenantId}/thumb_{$size}_{$filename}";
    }

    /**
     * Cria thumbnail para logo
     */
    private function createLogoThumbnail(UploadedFile $file, string $filename, int $tenantId, int $maxDimension): string
    {
        $originalPath = storage_path("app/public/logos/{$tenantId}/{$filename}");
        $thumbPath = storage_path("app/public/logos/{$tenantId}/thumb_{$maxDimension}_{$filename}");

        // Cria thumbnail mantendo proporção
        $image = $this->imageManager->read($file->getPathname());
        $image->scale($maxDimension, $maxDimension);
        $image->toJpeg(quality: 90)->save($thumbPath);

        return "logos/{$tenantId}/thumb_{$maxDimension}_{$filename}";
    }

    /**
     * Obtém informações de um arquivo
     */
    public function getFileInfo(string $path): ?array
    {
        if (! Storage::disk('public')->exists($path)) {
            return null;
        }

        $fullPath = Storage::disk('public')->path($path);

        return [
            'path' => $path,
            'url' => asset('storage/'.$path),
            'size' => Storage::disk('public')->size($path),
            'last_modified' => Storage::disk('public')->lastModified($path),
            'mime_type' => mime_content_type($fullPath),
        ];
    }

    /**
     * Lista arquivos de um diretório
     */
    public function listFiles(string $directory, int $tenantId): array
    {
        $path = "{$directory}/{$tenantId}";

        if (! Storage::disk('public')->exists($path)) {
            return [];
        }

        $files = Storage::disk('public')->files($path);

        return array_map(function ($file) {
            return $this->getFileInfo($file);
        }, $files);
    }

    /**
     * Limpa arquivos antigos de um diretório
     */
    public function cleanupOldFiles(string $directory, int $tenantId, int $daysOld = 30): int
    {
        $path = "{$directory}/{$tenantId}";
        $cutoffDate = now()->subDays($daysOld);

        if (! Storage::disk('public')->exists($path)) {
            return 0;
        }

        $files = Storage::disk('public')->allFiles($path);
        $deletedCount = 0;

        foreach ($files as $file) {
            $lastModified = Storage::disk('public')->lastModified($file);

            if ($lastModified < $cutoffDate->timestamp) {
                Storage::disk('public')->delete($file);
                $deletedCount++;
            }
        }

        return $deletedCount;
    }

    /**
     * Otimiza imagem
     */
    public function optimizeImage(string $sourcePath, string $targetPath, array $options = []): bool
    {
        try {
            $image = $this->imageManager->read(Storage::disk('public')->path($sourcePath));

            // Define qualidade padrão
            $quality = $options['quality'] ?? 85;

            // Redimensiona se necessário
            if (isset($options['width']) || isset($options['height'])) {
                if (isset($options['width']) && isset($options['height'])) {
                    $image->resize($options['width'], $options['height']);
                } elseif (isset($options['width'])) {
                    $image->scale(width: $options['width']);
                } elseif (isset($options['height'])) {
                    $image->scale(height: $options['height']);
                }
            }

            // Salva imagem otimizada
            $image->toJpeg(quality: $quality)->save(Storage::disk('public')->path($targetPath));

            return true;

        } catch (Exception $e) {
            throw new Exception('Erro ao otimizar imagem: '.$e->getMessage());
        }
    }
}
