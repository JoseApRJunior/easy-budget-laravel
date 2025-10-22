<?php

declare(strict_types=1);

namespace App\Services\Infrastructure;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Serviço para gerenciamento de internacionalização de e-mails.
 *
 * Centraliza a lógica de localização, fallback automático e
 * tratamento de erros de tradução para e-mails.
 */
class EmailLocalizationService
{
    /**
     * Cache de traduções por locale (TTL: 1 hora)
     */
    private const CACHE_TTL = 3600;

    /**
     * Locale padrão do sistema
     */
    private const DEFAULT_LOCALE = 'pt-BR';

    /**
     * Define o locale para e-mails.
     *
     * @param string|null $locale Locale desejado (pt-BR, en, etc.)
     * @return string Locale definido
     */
    public function setEmailLocale( ?string $locale = null ): string
    {
        $locale = $locale ?? self::DEFAULT_LOCALE;

        // Validar locale suportado
        if ( !$this->isLocaleSupported( $locale ) ) {
            Log::warning( 'Locale não suportado para e-mail', [
                'requested_locale' => $locale,
                'default_locale'   => self::DEFAULT_LOCALE,
            ] );
            $locale = self::DEFAULT_LOCALE;
        }

        // Configurar locale da aplicação
        App::setLocale( $locale );

        return $locale;
    }

    /**
     * Verifica se o locale é suportado.
     *
     * @param string $locale Locale para verificar
     * @return bool Verdadeiro se suportado
     */
    public function isLocaleSupported( string $locale ): bool
    {
        $supportedLocales = $this->getSupportedLocales();
        return in_array( $locale, array_keys( $supportedLocales ) );
    }

    /**
     * Obtém lista de locales suportados.
     *
     * @return array Array de locales suportados
     */
    public function getSupportedLocales(): array
    {
        return Cache::remember( 'supported_email_locales', self::CACHE_TTL, function () {
            $locales  = [];
            $langPath = resource_path( 'lang' );

            if ( is_dir( $langPath ) ) {
                foreach ( scandir( $langPath ) as $dir ) {
                    if ( $dir !== '.' && $dir !== '..' && is_dir( $langPath . '/' . $dir ) ) {
                        $emailFile = $langPath . '/' . $dir . '/emails.php';
                        if ( file_exists( $emailFile ) ) {
                            $locales[ $dir ] = $this->getLocaleName( $dir );
                        }
                    }
                }
            }

            return $locales;
        } );
    }

    /**
     * Obtém nome legível do locale.
     *
     * @param string $locale Código do locale
     * @return string Nome legível
     */
    private function getLocaleName( string $locale ): string
    {
        return match ( $locale ) {
            'pt-BR' => 'Português (Brasil)',
            'en'    => 'English',
            default => ucfirst( str_replace( [ '-', '_' ], ' ', $locale ) ),
        };
    }

    /**
     * Traduz uma chave com fallback automático.
     *
     * @param string $key Chave de tradução
     * @param array $replace Parâmetros de substituição
     * @param string|null $locale Locale específico
     * @return string Texto traduzido
     */
    public function translate( string $key, array $replace = [], ?string $locale = null ): string
    {
        $locale = $locale ?? App::getLocale();

        try {
            // Tentar traduzir na locale atual
            $translation = __( $key, $replace, $locale );

            // Verificar se a tradução foi bem-sucedida
            if ( $translation !== $key ) {
                return $translation;
            }

            // Fallback para locale padrão
            if ( $locale !== self::DEFAULT_LOCALE ) {
                $fallbackTranslation = __( $key, $replace, self::DEFAULT_LOCALE );
                if ( $fallbackTranslation !== $key ) {
                    Log::info( 'Fallback de tradução usado', [
                        'key'             => $key,
                        'original_locale' => $locale,
                        'fallback_locale' => self::DEFAULT_LOCALE,
                    ] );
                    return $fallbackTranslation;
                }
            }

            // Fallback para chave original se nenhuma tradução encontrada
            Log::warning( 'Tradução não encontrada', [
                'key'     => $key,
                'locale'  => $locale,
                'replace' => $replace,
            ] );

            return $key;
        } catch ( \Exception $e ) {
            Log::error( 'Erro na tradução de e-mail', [
                'key'    => $key,
                'locale' => $locale,
                'error'  => $e->getMessage(),
            ] );

            return $key;
        }
    }

    /**
     * Traduz com contexto específico de e-mail.
     *
     * @param string $section Seção do e-mail (verification, budget, etc.)
     * @param string $key Chave específica
     * @param array $replace Parâmetros de substituição
     * @param string|null $locale Locale específico
     * @return string Texto traduzido
     */
    public function translateEmail( string $section, string $key, array $replace = [], ?string $locale = null ): string
    {
        $fullKey = "emails.{$section}.{$key}";
        return $this->translate( $fullKey, $replace, $locale );
    }

    /**
     * Obtém configurações de formatação por locale.
     *
     * @param string $locale Locale específico
     * @return array Configurações de formatação
     */
    public function getLocaleConfig( string $locale ): array
    {
        $configs = [
            'pt-BR' => [
                'date_format'         => 'd/m/Y',
                'time_format'         => 'H:i:s',
                'datetime_format'     => 'd/m/Y H:i:s',
                'currency'            => 'R$',
                'decimal_separator'   => ',',
                'thousands_separator' => '.',
                'number_format'       => 'decimal_separator,thousands_separator',
            ],
            'en'    => [
                'date_format'         => 'm/d/Y',
                'time_format'         => 'H:i:s',
                'datetime_format'     => 'm/d/Y H:i:s',
                'currency'            => '$',
                'decimal_separator'   => '.',
                'thousands_separator' => ',',
                'number_format'       => 'decimal_separator,thousands_separator',
            ],
        ];

        return $configs[ $locale ] ?? $configs[ self::DEFAULT_LOCALE ];
    }

    /**
     * Formata moeda de acordo com o locale.
     *
     * @param float $amount Valor monetário
     * @param string|null $locale Locale específico
     * @return string Valor formatado
     */
    public function formatCurrency( float $amount, ?string $locale = null ): string
    {
        $locale = $locale ?? App::getLocale();
        $config = $this->getLocaleConfig( $locale );

        $formatted = number_format(
            $amount,
            2,
            $config[ 'decimal_separator' ],
            $config[ 'thousands_separator' ],
        );

        return $config[ 'currency' ] . ' ' . $formatted;
    }

    /**
     * Formata data de acordo com o locale.
     *
     * @param string|\DateTime $date Data para formatar
     * @param string|null $locale Locale específico
     * @return string Data formatada
     */
    public function formatDate( $date, ?string $locale = null ): string
    {
        $locale = $locale ?? App::getLocale();
        $config = $this->getLocaleConfig( $locale );

        if ( is_string( $date ) ) {
            $date = new \DateTime( $date );
        }

        return $date->format( $config[ 'date_format' ] );
    }

    /**
     * Formata data e hora de acordo com o locale.
     *
     * @param string|\DateTime $datetime Data e hora para formatar
     * @param string|null $locale Locale específico
     * @return string Data e hora formatadas
     */
    public function formatDateTime( $datetime, ?string $locale = null ): string
    {
        $locale = $locale ?? App::getLocale();
        $config = $this->getLocaleConfig( $locale );

        if ( is_string( $datetime ) ) {
            $datetime = new \DateTime( $datetime );
        }

        return $datetime->format( $config[ 'datetime_format' ] );
    }

    /**
     * Limpa cache de locales suportados.
     *
     * @return bool Sucesso na limpeza
     */
    public function clearLocaleCache(): bool
    {
        return Cache::forget( 'supported_email_locales' );
    }

    /**
     * Obtém estatísticas de uso de tradução.
     *
     * @return array Estatísticas de tradução
     */
    public function getTranslationStats(): array
    {
        return Cache::remember( 'email_translation_stats', self::CACHE_TTL, function () {
            $stats = [
                'total_requests'          => 0,
                'successful_translations' => 0,
                'fallback_used'           => 0,
                'errors'                  => 0,
                'locales_used'            => [],
            ];

            // Em um ambiente real, você poderia rastrear essas métricas
            // Por agora, retornamos estatísticas básicas
            $stats[ 'locales_used' ] = array_keys( $this->getSupportedLocales() );

            return $stats;
        } );
    }

}
