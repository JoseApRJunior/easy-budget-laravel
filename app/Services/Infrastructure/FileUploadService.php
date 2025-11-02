<?php

declare(strict_types=1);

namespace App\Services\Infrastructure;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Service para gerenciamento de uploads de arquivos.
 *
 * Centraliza operações de upload, validação e gerenciamento de arquivos
 * para diferentes tipos de conteúdo (logos, documentos, etc.).
 */
class FileUploadService
{
    /**
     * Faz upload de logo do provider.
     */
    public function uploadProviderLogo( UploadedFile $file, ?string $currentLogo = null ): string
    {
        // Delete old logo if exists
        if ( $currentLogo && Storage::disk( 'public' )->exists( $currentLogo ) ) {
            Storage::disk( 'public' )->delete( $currentLogo );
        }

        // Store new logo
        return $file->store( 'providers/logos', 'public' );
    }

    /**
     * Faz upload de arquivo genérico.
     */
    public function uploadFile( UploadedFile $file, string $directory, ?string $currentFile = null ): string
    {
        // Delete old file if exists
        if ( $currentFile && Storage::disk( 'public' )->exists( $currentFile ) ) {
            Storage::disk( 'public' )->delete( $currentFile );
        }

        // Store new file
        return $file->store( $directory, 'public' );
    }

    /**
     * Remove arquivo do storage.
     */
    public function deleteFile( string $filePath ): bool
    {
        if ( Storage::disk( 'public' )->exists( $filePath ) ) {
            return Storage::disk( 'public' )->delete( $filePath );
        }

        return false;
    }

    /**
     * Verifica se arquivo existe.
     */
    public function fileExists( string $filePath ): bool
    {
        return Storage::disk( 'public' )->exists( $filePath );
    }

    /**
     * Valida tipo de arquivo.
     */
    public function validateFileType( UploadedFile $file, array $allowedTypes ): bool
    {
        return in_array( $file->getMimeType(), $allowedTypes );
    }

    /**
     * Valida tamanho do arquivo.
     */
    public function validateFileSize( UploadedFile $file, int $maxSizeInKB ): bool
    {
        return $file->getSize() <= ( $maxSizeInKB * 1024 );
    }

}
