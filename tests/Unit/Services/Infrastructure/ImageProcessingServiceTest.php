<?php

namespace Tests\Unit\Services\Infrastructure;

use Tests\TestCase;
use App\Services\Infrastructure\ImageProcessingService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class ImageProcessingServiceTest extends TestCase
{
    private ImageProcessingService $service;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ImageProcessingService();
        Storage::fake('public');
    }

    /**
     * Test successful image upload processing
     */
    public function test_successful_image_upload(): void
    {
        // Create a fake image
        $file = UploadedFile::fake()->image('test.jpg', 800, 600);
        
        $result = $this->service->processUpload($file, 'test-directory');
        
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('original', $result);
        $this->assertArrayHasKey('path', $result['original']);
        $this->assertArrayHasKey('url', $result['original']);
        $this->assertArrayHasKey('dimensions', $result['original']);
        
        // Verify file was stored
        $this->assertTrue(Storage::disk('public')->exists($result['original']['path']));
    }

    /**
     * Test image upload with thumbnail generation
     */
    public function test_image_upload_with_thumbnail(): void
    {
        $file = UploadedFile::fake()->image('test.jpg', 800, 600);
        
        $result = $this->service->processUpload($file, 'test-directory', [
            'generate_thumbnail' => true
        ]);
        
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('thumbnail', $result);
        $this->assertNotNull($result['thumbnail']);
        $this->assertTrue(Storage::disk('public')->exists($result['thumbnail']['path']));
    }

    /**
     * Test image upload with multiple sizes
     */
    public function test_image_upload_with_sizes(): void
    {
        $file = UploadedFile::fake()->image('test.jpg', 1200, 900);
        
        $result = $this->service->processUpload($file, 'test-directory', [
            'generate_sizes' => true
        ]);
        
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('sizes', $result);
        $this->assertNotEmpty($result['sizes']);
        
        foreach ($result['sizes'] as $sizeName => $sizeData) {
            $this->assertTrue(Storage::disk('public')->exists($sizeData['path']));
        }
    }

    /**
     * Test image validation - invalid file type
     */
    public function test_invalid_file_type_rejection(): void
    {
        $file = UploadedFile::fake()->create('test.txt', 100, 'text/plain');
        
        $result = $this->service->processUpload($file, 'test-directory');
        
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('Extensão de arquivo não permitida', $result['error']);
    }

    /**
     * Test image validation - file too large
     */
    public function test_oversized_file_rejection(): void
    {
        // Create a file larger than the configured limit (10MB)
        $file = UploadedFile::fake()->create('large.jpg', 15000, 'image/jpeg');
        
        $result = $this->service->processUpload($file, 'test-directory');
        
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('excede o tamanho máximo permitido', $result['error']);
    }

    /**
     * Test image deletion
     */
    public function test_image_deletion(): void
    {
        $file = UploadedFile::fake()->image('test.jpg', 800, 600);
        
        // First upload an image
        $result = $this->service->processUpload($file, 'test-directory', [
            'generate_thumbnail' => true
        ]);
        
        $this->assertTrue($result['success']);
        
        // Now delete it
        $deleted = $this->service->deleteImage(
            $result['original']['path'],
            [$result['thumbnail']]
        );
        
        $this->assertTrue($deleted);
        $this->assertFalse(Storage::disk('public')->exists($result['original']['path']));
        $this->assertFalse(Storage::disk('public')->exists($result['thumbnail']['path']));
    }

    /**
     * Test image optimization
     */
    public function test_image_optimization(): void
    {
        $file = UploadedFile::fake()->image('test.jpg', 800, 600);
        
        // Upload image
        $result = $this->service->processUpload($file, 'test-directory');
        $this->assertTrue($result['success']);
        
        // Optimize it
        $optimized = $this->service->optimizeImage($result['original']['path'], 70);
        
        $this->assertTrue($optimized);
    }

    /**
     * Test image upload with resize options
     */
    public function test_image_upload_with_resize(): void
    {
        $file = UploadedFile::fake()->image('test.jpg', 2000, 1500);
        
        $result = $this->service->processUpload($file, 'test-directory', [
            'resize' => true,
            'max_width' => 1000,
            'max_height' => 1000
        ]);
        
        $this->assertTrue($result['success']);
        
        // Verify dimensions were resized
        $dimensions = $result['original']['dimensions'];
        $this->assertLessThanOrEqual(1000, $dimensions['width']);
        $this->assertLessThanOrEqual(1000, $dimensions['height']);
    }
}