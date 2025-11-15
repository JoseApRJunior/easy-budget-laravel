<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Abstracts\Controller;
use App\Services\Application\FileUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UploadController extends Controller
{
    public function __construct(private FileUploadService $fileUploadService)
    {
    }

    public function uploadImage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:4096',
            'resize.width' => 'nullable|integer|min:50|max:3000',
            'resize.height' => 'nullable|integer|min:50|max:3000',
            'watermark.enabled' => 'nullable|boolean',
            'watermark.position' => 'nullable|in:top-right,top-left,bottom-right,bottom-left',
            'watermark.opacity' => 'nullable|integer|min:0|max:100',
            'watermark.x' => 'nullable|integer|min:0|max:1000',
            'watermark.y' => 'nullable|integer|min:0|max:1000',
            'watermark.width' => 'nullable|integer|min:10|max:2000',
            'watermark.height' => 'nullable|integer|min:10|max:2000',
        ]);

        $tenantId = auth()->user()->tenant_id;

        $options = [
            'resize' => $request->input('resize', []),
            'watermark' => $request->input('watermark', []),
        ];

        $result = $this->fileUploadService->uploadImageWithProcessing($request->file('image'), $tenantId, $options);

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }
}