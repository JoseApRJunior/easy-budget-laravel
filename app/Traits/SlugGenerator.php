<?php

declare(strict_types=1);

namespace App\Traits;

use App\Contracts\SlugAwareRepositoryInterface;
use Exception;

/**
 * Trait para geração de slugs únicos.
 *
 * Fornece métodos para gerar slugs básicos e únicos (com verificação em repositório).
 * Elimina duplicação em serviços que precisam de geração de slugs.
 *
 * USO:
 * - generateSlug($text): Slug básico
 * - generateUniqueSlug($text, $repository, $tenantId = null, $excludeId = null): Slug único tenant-aware
 * - generateDefaultSlug($text): Slug padrão sem tradução
 */
trait SlugGenerator
{
    /**
     * Gera um slug básico a partir de um texto.
     *
     * @param string $text Texto para gerar o slug
     * @return string Slug gerado
     */
    protected function generateSlug( string $text ): string
    {
        return $this->generateDefaultSlug( $text );
    }

    /**
     * Gera um slug único tenant-aware usando repositório.
     *
     * @param string $text Texto base
     * @param object $repository Repositório para verificação
     * @param int|null $tenantId Tenant ID para scope (se suportado)
     * @param int|null $excludeId ID a excluir (para updates)
     * @return string Slug único
     * @throws Exception Se não conseguir gerar
     */
    protected function generateUniqueSlug( string $text, object $repository, ?int $tenantId = null, ?int $excludeId = null ): string
    {
        $baseSlug = $this->generateSlug( $text );
        $slug     = $baseSlug;
        $counter  = 1;

        while ( $this->slugExists( $repository, $slug, $tenantId, $excludeId ) ) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
            if ( $counter > 100 ) {
                throw new Exception( 'Não foi possível gerar um slug único após 100 tentativas.' );
            }
        }

        return $slug;
    }

    /**
     * Verifica se slug existe no repositório (tenant-aware se possível).
     *
     * @param object $repository Repositório
     * @param string $slug Slug a verificar
     * @param int|null $tenantId Tenant ID
     * @param int|null $excludeId ID a excluir
     * @return bool True se existe
     */
    private function slugExists( object $repository, string $slug, ?int $tenantId = null, ?int $excludeId = null ): bool
    {
        if ( !method_exists( $repository, 'existsBySlug' ) ) {
            throw new Exception( 'Repositório deve implementar o método existsBySlug.' );
        }
        return $repository->existsBySlug( $slug, $tenantId, $excludeId );
    }

    /**
     * Carrega dicionário de traduções para roles (específico).
     *
     * @return array Dicionário PT-BR to English slug
     */
    protected function loadRoleTranslations(): array
    {
        return [ 
            // Administração
            'administrador'          => 'admin',
            'admin'                  => 'admin',
            'gerente'                => 'manager',
            'supervisor'             => 'supervisor',
            'coordenador'            => 'coordinator',
            'diretor'                => 'director',
            'presidente'             => 'president',
            'ceo'                    => 'ceo',
            'cto'                    => 'cto',
            'cfo'                    => 'cfo',

            // Usuários
            'usuário'                => 'user',
            'cliente'                => 'client',
            'visitante'              => 'visitor',
            'convidado'              => 'guest',
            'membro'                 => 'member',
            'assinante'              => 'subscriber',

            // Técnicos
            'desenvolvedor'          => 'developer',
            'programador'            => 'programmer',
            'analista'               => 'analyst',
            'designer'               => 'designer',
            'arquiteto'              => 'architect',
            'engenheiro'             => 'engineer',
            'técnico'                => 'technician',
            'suporte'                => 'support',
            'helpdesk'               => 'helpdesk',

            // Vendas e Marketing
            'vendedor'               => 'salesperson',
            'consultor'              => 'consultant',
            'representante'          => 'representative',
            'marketing'              => 'marketing',
            'comercial'              => 'sales',
            'atendimento'            => 'customer-service',

            // Financeiro
            'contador'               => 'accountant',
            'financeiro'             => 'financial',
            'tesoureiro'             => 'treasurer',
            'auditor'                => 'auditor',
            'analista financeiro'    => 'financial-analyst',

            // Recursos Humanos
            'rh'                     => 'hr',
            'recursos humanos'       => 'human-resources',
            'recrutador'             => 'recruiter',
            'psicólogo'              => 'psychologist',

            // Operacional
            'operador'               => 'operator',
            'operacional'            => 'operational',
            'produção'               => 'production',
            'qualidade'              => 'quality',
            'logística'              => 'logistics',
            'almoxarife'             => 'warehouse',

            // Jurídico
            'advogado'               => 'lawyer',
            'jurídico'               => 'legal',
            'compliance'             => 'compliance',

            // Educação
            'professor'              => 'teacher',
            'instrutor'              => 'instructor',
            'tutor'                  => 'tutor',
            'coordenador pedagógico' => 'pedagogical-coordinator',

            // Saúde
            'médico'                 => 'doctor',
            'enfermeiro'             => 'nurse',
            'fisioterapeuta'         => 'physiotherapist',
            'dentista'               => 'dentist',

            // Moderação
            'moderador'              => 'moderator',
            'editor'                 => 'editor',
            'revisor'                => 'reviewer',
            'curador'                => 'curator'
        ];
    }

    /**
     * Traduz um texto usando um dicionário de traduções.
     *
     * @param string $text Texto a ser traduzido
     * @param array $dict Dicionário de traduções
     * @return string|null Tradução encontrada ou null se não encontrar
     */
    protected function translateWithDictionary( string $text, array $dict ): ?string
    {
        $nameLower = mb_strtolower( $text, 'UTF-8' );
        if ( isset( $dict[ $nameLower ] ) ) {
            return $dict[ $nameLower ];
        }
        foreach ( $dict as $keyword => $translation ) {
            if ( strpos( $nameLower, $keyword ) !== false ) {
                return $translation;
            }
        }
        return null;
    }

    /**
     * Gera slug padrão sem tradução.
     *
     * @param string $text Texto
     * @return string Slug
     */
    private function generateDefaultSlug( string $text ): string
    {
        $slug = mb_strtolower( trim( $text ), 'UTF-8' );

        $slug = iconv( 'UTF-8', 'ASCII//TRANSLIT', $slug );

        $slug = preg_replace( '/[^a-z0-9]+/', '-', $slug );

        $slug = trim( preg_replace( '/-+/', '-', $slug ), '-' );

        if ( strlen( $slug ) > 100 ) {
            $slug = substr( $slug, 0, 100 );
            $slug = rtrim( $slug, '-' );
        }

        return $slug;
    }

}
