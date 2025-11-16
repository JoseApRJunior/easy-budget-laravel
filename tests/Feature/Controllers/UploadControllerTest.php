<?php

namespace Tests\Feature\Controllers;

use Tests\TestCase;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UploadControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email_verified_at' => now(),
        ]);
        
        Storage::fake('public');
    }

    /**
     * Test successful image upload
     */
    public function test_successful_image_upload(): void
    {
        $this->actingAs($this->user);
        
        $file = UploadedFile::fake()->image('test.jpg', 800, 600);
        
        $response = $this->postJson('/upload/image', [
            'image' => $file,
            'directory' => 'test-images',
            'resize' => true,
            'generate_thumbnail' => true,
        ]);
        
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'success',
                    'original' => [
                        'path',
                        'url',
                        'dimensions',
                    ],
                    'thumbnail' => [
                        'path',
                        'url',
                        'dimensions',
                    ],
                ],
                'message',
            ]);
        
        // Verify file was stored
        $data = $response->json('data');
        $this->assertTrue(Storage::disk('public')->exists($data['original']['path']));
        $this->assertTrue(Storage::disk('public')->exists($data['thumbnail']['path']));
    }

    /**
     * Test image upload without authentication
     */
    public function test_image_upload_requires_authentication(): void
    {
        $file = UploadedFile::fake()->image('test.jpg', 800, 600);
        
        $response = $this->postJson('/upload/image', [
            'image' => $file,
        ]);
        
        $response->assertStatus(401);
    }

    /**
     * Test image upload with invalid file
     */
    public function test_image_upload_with_invalid_file(): void
    {
        $this->actingAs($this->user);
        
        $file = UploadedFile::fake()->create('test.txt', 100, 'text/plain');
        
        $response = $this->postJson('/upload/image', [
            'image' => $file,
        ]);
        
        $response->assertStatus(422);
    }

    /**
     * Test image upload with oversized file
     */
    public function test_image_upload_with_oversized_file(): void
    {
        $this->actingAs($this->user);
        
        // Create a file larger than 10MB
        $file = UploadedFile::fake()->create('large.jpg', 15000, 'image/jpeg');
        
        $response = $this->postJson('/upload/image', [
            'image' => $file,
        ]);
        
        $response->assertStatus(422);
    }

    /**
     * Test successful image deletion
     */
    public function test_successful_image_deletion(): void
    {
        $this->actingAs($this->user);
        
        // First upload an image
        $file = UploadedFile::fake()->image('test.jpg', 800, 600);
        
        $uploadResponse = $this->postJson('/upload/image', [
            'image' => $file,
            'generate_thumbnail' => true,
        ]);
        
        $uploadResponse->assertStatus(200);
        $data = $uploadResponse->json('data');
        
        // Now delete it
        $deleteResponse = $this->deleteJson('/upload/image', [
            'path' => $data['original']['path'],
            'thumbnail_path' => $data['thumbnail']['path'],
        ]);
        
        $deleteResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Imagem deletada com sucesso!',
            ]);
    }

    /**
     * Test image deletion without authentication
     */
    public function test_image_deletion_requires_authentication(): void
    {
        $response = $this->deleteJson('/upload/image', [
            'path' => 'test/path.jpg',
        ]);
        
        $response->assertStatus(401);
    }

    /**
     * Test successful image optimization
     */
    public function test_successful_image_optimization(): void
    {
        $this->actingAs($this->user);
        
        // First upload an image
        $file = UploadedFile::fake()->image('test.jpg', 800, 600);
        
        $uploadResponse = $this->postJson('/upload/image', [
            'image' => $file,
        ]);
        
        $uploadResponse->assertStatus(200);
        $data = $uploadResponse->json('data');
        
        // Now optimize it
        $optimizeResponse = $this->postJson('/upload/image/optimize', [
            'path' => $data['original']['path'],
            'quality' => 70,
        ]);
        
        $optimizeResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Imagem otimizada com sucesso!',
            ]);
    }

    /**
     * Test image optimization without authentication
     */
    public function test_image_optimization_requires_authentication(): void
    {
        $response = $this->postJson('/upload/image/optimize', [
            'path' => 'test/path.jpg',
        ]);
        
        $response->assertStatus(401);
    }

    /**
     * Test image upload with custom options
     */
    public function test_image_upload_with_custom_options(): void
    {
        $this->actingAs($this->user);
        
        $file = UploadedFile::fake()->image('test.jpg', 2000, 1500);
        
        $response = $this->postJson('/upload/image', [
            'image' => $file,
            'max_width' => 1000,
            'max_height' => 1000,
            'quality' => 70,
            'generate_sizes' => true,
            'watermark' => false,
        ]);
        
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'data' => [
                    'sizes',
                ],
            ]);
        
        $data = $response->json('data');
        $this->assertArrayHasKey('sizes', $data);
        $this->assertNotEmpty($data['sizes']);
    }

    /**
     * Test email verification requirement
     */
    public function test_email_verification_required(): void
    {
        $unverifiedUser = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email_verified_at' => null,
        ]);
        
        $this->actingAs($unverifiedUser);
        
        $file = UploadedFile::fake()->image('test.jpg', 800, 600);
        
        $response = $this->postJson('/upload/image', [
            'image' => $file,
        ]);
        
        // Should redirect to email verification
        $response->assertStatus(302);
    }
}