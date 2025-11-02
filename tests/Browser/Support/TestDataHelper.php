<?php

namespace Tests\Browser\Support;

class TestDataHelper
{
    /**
     * Dados válidos para teste do formulário de business.
     */
    public static function validBusinessData(): array
    {
        return [
            'personal'     => [
                'first_name'     => 'José',
                'last_name'      => 'Silva',
                'birth_date'     => '1990-01-15',
                'email_personal' => 'jose.silva@email.com',
                'phone_personal' => '(11) 99999-9999',
            ],
            'professional' => [
                'company_name'        => 'Empresa Exemplo LTDA',
                'cnpj'                => '12.345.678/0001-90',
                'cpf'                 => '123.456.789-00',
                'area_of_activity_id' => '1',
                'profession_id'       => '1',
                'description'         => 'Empresa especializada em desenvolvimento de software e soluções tecnológicas.',
            ],
            'contact'      => [
                'email_business' => 'contato@empresaexemplo.com.br',
                'phone_business' => '(11) 3333-3333',
                'website'        => 'https://empresaexemplo.com.br',
            ],
            'address'      => [
                'cep'            => '01234-567',
                'address'        => 'Rua das Flores',
                'address_number' => '123',
                'neighborhood'   => 'Centro',
                'city'           => 'São Paulo',
                'state'          => 'SP',
            ],
        ];
    }

    /**
     * Dados inválidos para teste de validação.
     */
    public static function invalidBusinessData(): array
    {
        return [
            'personal'     => [
                'first_name'     => '', // Campo obrigatório vazio
                'last_name'      => '', // Campo obrigatório vazio
                'birth_date'     => '2030-01-15', // Data futura (menor de 18 anos)
                'email_personal' => 'email-invalido', // Email inválido
                'phone_personal' => '123', // Telefone inválido
            ],
            'professional' => [
                'company_name'        => '',
                'cnpj'                => '00.000.000/0000-00', // CNPJ inválido
                'cpf'                 => '000.000.000-00', // CPF inválido
                'area_of_activity_id' => '', // Campo obrigatório
                'profession_id'       => '', // Campo obrigatório
                'description'         => str_repeat( 'a', 300 ), // Texto muito longo (máximo 250)
            ],
            'contact'      => [
                'email_business' => 'email-business-invalido',
                'phone_business' => 'abc',
                'website'        => 'url-invalida',
            ],
            'address'      => [
                'cep'            => '00000-000', // CEP inválido
                'address'        => '',
                'address_number' => '',
                'neighborhood'   => '',
                'city'           => '',
                'state'          => '',
            ],
        ];
    }

    /**
     * Dados mínimos para teste.
     */
    public static function minimalBusinessData(): array
    {
        return [
            'personal'     => [
                'first_name'     => 'João',
                'last_name'      => 'Santos',
                'birth_date'     => '1985-06-20',
                'email_personal' => 'joao.santos@teste.com',
                'phone_personal' => '(11) 8888-8888',
            ],
            'professional' => [
                'company_name'        => 'Tech Solutions',
                'cnpj'                => '11.222.333/0001-01',
                'cpf'                 => '111.222.333-44',
                'area_of_activity_id' => '2',
                'profession_id'       => '3',
                'description'         => 'Desenvolvimento de soluções tecnológicas.',
            ],
            'contact'      => [
                'email_business' => 'contato@techsolutions.com.br',
                'phone_business' => '(11) 2222-2222',
                'website'        => 'https://techsolutions.com.br',
            ],
            'address'      => [
                'cep'            => '05555-555',
                'address'        => 'Av. Paulista',
                'address_number' => '1000',
                'neighborhood'   => 'Bela Vista',
                'city'           => 'São Paulo',
                'state'          => 'SP',
            ],
        ];
    }

    /**
     * Gera arquivo de teste para logo.
     */
    public static function generateTestLogo( string $fileName = 'test-logo.png' ): string
    {
        $storagePath = storage_path( 'app/public/test-logos' );

        // Cria diretório se não existir
        if ( !file_exists( $storagePath ) ) {
            mkdir( $storagePath, 0755, true );
        }

        $filePath = $storagePath . '/' . $fileName;

        // Cria uma imagem PNG simples para teste (1x1 pixel transparente)
        $image       = imagecreate( 100, 100 );
        $transparent = imagecolorallocatealpha( $image, 255, 255, 255, 127 );
        imagefill( $image, 0, 0, $transparent );

        // Adiciona texto simples
        $textColor = imagecolorallocate( $image, 0, 0, 0 );
        imagestring( $image, 3, 20, 40, 'LOGO', $textColor );

        imagepng( $image, $filePath );
        imagedestroy( $image );

        return $filePath;
    }

    /**
     * Remove arquivos de teste criados.
     */
    public static function cleanupTestFiles(): void
    {
        $storagePath = storage_path( 'app/public/test-logos' );

        if ( is_dir( $storagePath ) ) {
            $files = glob( $storagePath . '/*' );
            foreach ( $files as $file ) {
                if ( is_file( $file ) ) {
                    unlink( $file );
                }
            }
        }
    }

    /**
     * Dados para teste de atualização (parcial).
     */
    public static function partialUpdateData(): array
    {
        return [
            'personal'     => [
                'first_name'     => 'Maria',
                'phone_personal' => '(11) 7777-7777',
            ],
            'professional' => [
                'description' => 'Descrição atualizada para teste.',
            ],
            'contact'      => [
                'website' => 'https://novowebsite.com',
            ],
        ];
    }

}
