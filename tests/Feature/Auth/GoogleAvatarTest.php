<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Teste para validação de avatars do Google OAuth
 *
 * Verifica se a solução implementada permite exibir corretamente
 * avatares externos do Google OAuth sem tentar tratá-los como arquivos locais.
 */
class GoogleAvatarTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function usuario_com_google_avatar_url_externa_deve_exibir_corretamente(): void
    {
        // Arrange
        $user = User::factory()->create( [
            'avatar'      => null, // Sem avatar local
            'google_id'   => 'google123',
            'google_data' => [
                'id'     => 'google123',
                'name'   => 'João Silva',
                'email'  => 'joao@gmail.com',
                'avatar' => 'https://lh3.googleusercontent.com/a/ACg8ocK7nJ_tR-jpfuZ5X_1aS7hyCEY9TzhAnK_ryBrmN0eBvJDZ0LZg-w=s96-c'
            ]
        ] );

        // Act
        $avatarUrl = $user->getAvatarOrGoogleAvatarAttribute();

        // Assert
        $this->assertEquals( 'https://lh3.googleusercontent.com/a/ACg8ocK7nJ_tR-jpfuZ5X_1aS7hyCEY9TzhAnK_ryBrmN0eBvJDZ0LZg-w=s96-c', $avatarUrl );
    }

    /** @test */
    public function usuario_com_avatar_local_deve_priorizar_avatar_local(): void
    {
        // Arrange
        $user = User::factory()->create( [
            'avatar'      => 'avatars/123/profile.jpg', // Avatar local
            'google_id'   => 'google123',
            'google_data' => [
                'id'     => 'google123',
                'name'   => 'João Silva',
                'email'  => 'joao@gmail.com',
                'avatar' => 'https://lh3.googleusercontent.com/a/ACg8ocK7nJ_tR-jpfuZ5X_1aS7hyCEY9TzhAnK_ryBrmN0eBvJDZ0LZg-w=s96-c'
            ]
        ] );

        // Act
        $avatarUrl = $user->getAvatarOrGoogleAvatarAttribute();

        // Assert
        $this->assertStringContainsString( 'storage/avatars/123/profile.jpg', $avatarUrl );
        $this->assertStringNotContainsString( 'lh3.googleusercontent.com', $avatarUrl );
    }

    /** @test */
    public function usuario_sem_avatar_deve_usar_avatar_padrao(): void
    {
        // Arrange
        $user = User::factory()->create( [
            'avatar'      => null,
            'google_id'   => null,
            'google_data' => null
        ] );

        // Act
        $avatarUrl = $user->getAvatarOrGoogleAvatarAttribute();

        // Assert
        $this->assertEquals( asset( 'img/default_avatar.png' ), $avatarUrl );
    }

    /** @test */
    public function metodo_getAvatarUrl_deve_distinguir_url_externa_de_arquivo_local(): void
    {
        // Teste para URL externa
        $user1 = User::factory()->create( [
            'avatar' => 'https://example.com/image.jpg'
        ] );
        $this->assertEquals( 'https://example.com/image.jpg', $user1->getAvatarUrlAttribute() );

        // Teste para arquivo local
        $user2 = User::factory()->create( [
            'avatar' => 'avatars/123/profile.jpg'
        ] );
        $this->assertStringContainsString( 'storage/avatars/123/profile.jpg', $user2->getAvatarUrlAttribute() );

        // Teste para avatar vazio
        $user3 = User::factory()->create( [
            'avatar' => null
        ] );
        $this->assertEquals( asset( 'img/default_avatar.png' ), $user3->getAvatarUrlAttribute() );
    }

}
