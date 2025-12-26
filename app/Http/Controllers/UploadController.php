<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Abstracts\Controller;
use App\Services\Infrastructure\ImageProcessingService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UploadController extends Controller
{
    public function __construct(private ImageProcessingService $imageProcessingService) {}

    /**
     * Upload de imagem com processamento
     */
    public function uploadImage(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:10240',
                'directory' => 'nullable|string|max:255',
                'resize' => 'nullable|boolean',
                'max_width' => 'nullable|integer|min:50|max:3000',
                'max_height' => 'nullable|integer|min:50|max:3000',
                'generate_thumbnail' => 'nullable|boolean',
                'generate_sizes' => 'nullable|boolean',
                'watermark' => 'nullable|boolean',
                'quality' => 'nullable|integer|min:1|max:100',
            ]);

            $directory = $validated['directory'] ?? 'uploads/'.auth()->user()->tenant_id.'/images';

            $options = [
                'resize' => $validated['resize'] ?? true,
                'max_width' => $validated['max_width'] ?? null,
                'max_height' => $validated['max_height'] ?? null,
                'generate_thumbnail' => $validated['generate_thumbnail'] ?? true,
                'generate_sizes' => $validated['generate_sizes'] ?? false,
                'watermark' => $validated['watermark'] ?? false,
                'quality' => $validated['quality'] ?? null,
            ];

            $result = $this->imageProcessingService->processUpload(
                $request->file('image'),
                $directory,
                $options
            );

            if (! $result['success']) {
                return response()->json([
                    'success' => false,
                    'error' => $result['error'] ?? 'Erro ao processar imagem',
                ], 400);
            }

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Imagem enviada e processada com sucesso!',
            ]);

        } catch (Exception $e) {
            Log::error('Erro no upload de imagem', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'tenant_id' => auth()->user()->tenant_id,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erro ao processar imagem. Por favor, tente novamente.',
            ], 500);
        }
    }

    /**
     * Deletar imagem
     */
    public function deleteImage(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'path' => 'required|string',
                'thumbnail_path' => 'nullable|string',
                'sizes' => 'nullable|array',
                'sizes.*.path' => 'nullable|string',
            ]);

            $derivatives = [];
            if (isset($validated['thumbnail_path'])) {
                $derivatives[] = ['path' => $validated['thumbnail_path']];
            }
            if (isset($validated['sizes'])) {
                $derivatives = array_merge($derivatives, $validated['sizes']);
            }

            $deleted = $this->imageProcessingService->deleteImage(
                $validated['path'],
                $derivatives
            );

            if ($deleted) {
                return response()->json([
                    'success' => true,
                    'message' => 'Imagem deletada com sucesso!',
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => 'Erro ao deletar imagem.',
            ], 400);

        } catch (Exception $e) {
            Log::error('Erro ao deletar imagem', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'path' => $request->input('path'),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erro ao deletar imagem.',
            ], 500);
        }
    }

    /**
     * Otimizar imagem existente
     */
    public function optimizeImage(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'path' => 'required|string',
                'quality' => 'nullable|integer|min:1|max:100',
            ]);

            $optimized = $this->imageProcessingService->optimizeImage(
                $validated['path'],
                $validated['quality'] ?? null
            );

            if ($optimized) {
                return response()->json([
                    'success' => true,
                    'message' => 'Imagem otimizada com sucesso!',
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => 'Erro ao otimizar imagem.',
            ], 400);

        } catch (Exception $e) {
            Log::error('Erro ao otimizar imagem', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'path' => $request->input('path'),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erro ao otimizar imagem.',
            ], 500);
        }
    }
}
