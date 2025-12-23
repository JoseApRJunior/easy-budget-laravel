<?php

namespace App\Services\Infrastructure;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Illuminate\Http\UploadedFile;

/**
 * Serviço para processamento de imagens
 * Responsável por upload, redimensionamento, watermark e otimização
 */
class ImageProcessingService
{
    protected array $config;
    
    public function __construct()
    {
        $this->config = config('upload.images', [
            'max_width' => 1920,
            'max_height' => 1080,
            'quality' => 85,
            'thumbnail_width' => 300,
            'thumbnail_height' => 300,
            'watermark_enabled' => false,
            'watermark_position' => 'bottom-right',
            'watermark_opacity' => 0.5,
            'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
            'max_size' => 10240, // 10MB
        ]);
    }

    /**
     * Processar upload de imagem
     */
    public function processUpload(UploadedFile $file, string $directory = 'uploads', array $options = []): array
    {
        try {
            // Validar arquivo
            $this->validateFile($file);
            
            // Gerar nome único
            $filename = $this->generateFilename($file);
            $path = $directory . '/' . date('Y/m');
            
            // Processar imagem principal
            $processedImage = $this->processImage($file, $options);
            
            // Salvar imagem original processada
            $fullPath = $path . '/' . $filename;
            Storage::disk('public')->put($fullPath, $processedImage->encode());
            
            // Gerar thumbnail se solicitado
            $thumbnailPath = null;
            if ($options['generate_thumbnail'] ?? true) {
                $thumbnailPath = $this->generateThumbnail($file, $path, $filename);
            }
            
            // Gerar diferentes tamanhos se solicitado
            $sizes = [];
            if ($options['generate_sizes'] ?? false) {
                $sizes = $this->generateSizes($file, $path, $filename, $options['sizes'] ?? []);
            }
            
            return [
                'success' => true,
                'original' => [
                    'path' => $fullPath,
                    'url' => Storage::disk('public')->url($fullPath),
                    'size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                    'dimensions' => $this->getImageDimensions($processedImage),
                ],
                'thumbnail' => $thumbnailPath,
                'sizes' => $sizes,
            ];
            
        } catch (Exception $e) {
            Log::error('Erro ao processar upload de imagem', [
                'error' => $e->getMessage(),
                'file' => $file->getClientOriginalName(),
                'directory' => $directory,
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Validar arquivo de imagem
     */
    protected function validateFile(UploadedFile $file): void
    {
        // Verificar tamanho
        if ($file->getSize() > $this->config['max_size'] * 1024) {
            throw new Exception('O arquivo excede o tamanho máximo permitido de ' . ($this->config['max_size'] / 1024) . 'MB');
        }
        
        // Verificar extensão
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, $this->config['allowed_extensions'])) {
            throw new Exception('Extensão de arquivo não permitida. Extensões permitidas: ' . implode(', ', $this->config['allowed_extensions']));
        }
        
        // Verificar MIME type
        $mimeType = $file->getMimeType();
        $allowedMimeTypes = [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
        ];
        
        if (!in_array($mimeType, $allowedMimeTypes)) {
            throw new Exception('Tipo de arquivo não permitido.');
        }
    }

    /**
     * Gerar nome único para o arquivo
     */
    protected function generateFilename(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        $timestamp = time();
        $random = bin2hex(random_bytes(8));
        
        return "img_{$timestamp}_{$random}.{$extension}";
    }

    /**
     * Processar imagem (redimensionar, otimizar, aplicar watermark)
     */
    protected function processImage(UploadedFile $file, array $options = []): \Intervention\Image\Image
    {
        $image = Image::make($file);
        
        // Redimensionar se necessário
        if ($options['resize'] ?? true) {
            $maxWidth = $options['max_width'] ?? $this->config['max_width'];
            $maxHeight = $options['max_height'] ?? $this->config['max_height'];
            
            $image->resize($maxWidth, $maxHeight, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
        }
        
        // Aplicar watermark se habilitado
        if ($this->config['watermark_enabled'] && ($options['watermark'] ?? true)) {
            $this->applyWatermark($image);
        }
        
        // Otimizar qualidade
        $quality = $options['quality'] ?? $this->config['quality'];
        $image->encode($image->extension, $quality);
        
        return $image;
    }

    /**
     * Gerar thumbnail
     */
    protected function generateThumbnail(UploadedFile $file, string $path, string $filename): array
    {
        $thumbnailFilename = 'thumb_' . $filename;
        $thumbnailPath = $path . '/' . $thumbnailFilename;
        
        $image = Image::make($file);
        
        $image->fit(
            $this->config['thumbnail_width'],
            $this->config['thumbnail_height'],
            function ($constraint) {
                $constraint->upsize();
            }
        );
        
        Storage::disk('public')->put($thumbnailPath, $image->encode());
        
        return [
            'path' => $thumbnailPath,
            'url' => Storage::disk('public')->url($thumbnailPath),
            'dimensions' => $this->getImageDimensions($image),
        ];
    }

    /**
     * Gerar diferentes tamanhos da imagem
     */
    protected function generateSizes(UploadedFile $file, string $path, string $filename, array $sizes): array
    {
        $results = [];
        $defaultSizes = [
            'small' => ['width' => 300, 'height' => 300],
            'medium' => ['width' => 600, 'height' => 600],
            'large' => ['width' => 1200, 'height' => 1200],
        ];
        
        $sizesToGenerate = array_merge($defaultSizes, $sizes);
        
        foreach ($sizesToGenerate as $sizeName => $sizeConfig) {
            $sizeFilename = $sizeName . '_' . $filename;
            $sizePath = $path . '/' . $sizeFilename;
            
            $image = Image::make($file);
            
            $image->resize($sizeConfig['width'], $sizeConfig['height'], function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
            
            Storage::disk('public')->put($sizePath, $image->encode());
            
            $results[$sizeName] = [
                'path' => $sizePath,
                'url' => Storage::disk('public')->url($sizePath),
                'dimensions' => $this->getImageDimensions($image),
            ];
        }
        
        return $results;
    }

    /**
     * Aplicar watermark na imagem
     */
    protected function applyWatermark(\Intervention\Image\Image $image): void
    {
        $watermarkPath = $this->config['watermark_path'] ?? null;
        
        if (!$watermarkPath || !file_exists($watermarkPath)) {
            Log::warning('Watermark não encontrado', ['path' => $watermarkPath]);
            return;
        }
        
        $watermark = Image::make($watermarkPath);
        
        // Redimensionar watermark proporcionalmente
        $watermarkWidth = $image->width() * 0.2; // 20% da largura da imagem
        $watermarkHeight = $watermark->height() * ($watermarkWidth / $watermark->width());
        
        $watermark->resize($watermarkWidth, $watermarkHeight);
        
        // Aplicar transparência
        $watermark->opacity($this->config['watermark_opacity'] * 100);
        
        // Posicionar watermark
        $position = $this->config['watermark_position'];
        $image->insert($watermark, $position, 10, 10);
    }

    /**
     * Obter dimensões da imagem
     */
    protected function getImageDimensions(\Intervention\Image\Image $image): array
    {
        return [
            'width' => $image->width(),
            'height' => $image->height(),
        ];
    }

    /**
     * Deletar imagem e seus derivados
     */
    public function deleteImage(string $path, array $derivatives = []): bool
    {
        try {
            $deleted = true;
            
            // Deletar imagem principal
            if (Storage::disk('public')->exists($path)) {
                $deleted = $deleted && Storage::disk('public')->delete($path);
            }
            
            // Deletar derivados (thumbnail, sizes)
            foreach ($derivatives as $derivative) {
                if (isset($derivative['path']) && Storage::disk('public')->exists($derivative['path'])) {
                    $deleted = $deleted && Storage::disk('public')->delete($derivative['path']);
                }
            }
            
            return $deleted;
            
        } catch (Exception $e) {
            Log::error('Erro ao deletar imagem', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    /**
     * Otimizar imagem existente
     */
    public function optimizeImage(string $path, int $quality = null): bool
    {
        try {
            if (!Storage::disk('public')->exists($path)) {
                throw new Exception('Imagem não encontrada');
            }
            
            $imageContent = Storage::disk('public')->get($path);
            $image = Image::make($imageContent);
            
            $quality = $quality ?? $this->config['quality'];
            $optimizedContent = $image->encode($image->extension, $quality);
            
            Storage::disk('public')->put($path, $optimizedContent);
            
            return true;
            
        } catch (Exception $e) {
            Log::error('Erro ao otimizar imagem', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }
}