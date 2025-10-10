<?php

namespace App\Helpers;

use App\Models\Resource;

class BladeHelper
{
    /**
     * Gera HTML para alertas do sistema.
     *
     * @param string $type Tipo do alerta (error, success, message, warning)
     * @param string $message Mensagem do alerta
     * @return string HTML do alerta
     */
    public function alert( string $type, string $message ): string
    {
        $flashTypes = [
            'error'   => 'danger',
            'success' => 'success',
            'message' => 'info',
            'warning' => 'warning'
        ];

        $bootstrapType = $flashTypes[ $type ] ?? 'info';

        return '<div class="alert alert-' . $bootstrapType . ' alert-dismissible fade show text-center" role="alert">' .
            $message .
            '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' .
            '</div>';
    }

    /**
     * Verifica se um recurso está ativo e renderiza o conteúdo apropriado.
     *
     * @param string $featureSlug Slug do recurso
     * @param string $content Conteúdo a ser renderizado
     * @param bool $condition Condição adicional para verificação
     * @return string HTML renderizado
     */
    public function checkFeature( string $featureSlug, string $content, bool $condition = true ): string
    {
        $resource = $this->getResource( $featureSlug );

        if ( $condition && $resource && isset( $resource->status ) && $resource->status === Resource::STATUS_INACTIVE ) {
            $warningHtml = '<div class="alert alert-warning m-2 d-flex" role="alert">' .
                '<i class="bi bi-exclamation-triangle-fill me-2"></i>' .
                '<div>Recurso desativado temporariamente</div>' .
                '</div>';

            $disabledContent = '<div class="feature-content feature-disabled">' . $content . '</div>';

            return $warningHtml . $disabledContent;
        }

        return '<div class="feature-content">' . $content . '</div>';
    }

    /**
     * Obtém um recurso pelo slug.
     * Usa withoutTenant() para buscar recursos globais independentemente do tenant atual.
     *
     * @param string $slug Slug do recurso
     * @return Resource|null Recurso encontrado ou null
     */
    private function getResource( string $slug ): ?Resource
    {
        return Resource::withoutTenant()->where( 'slug', $slug )->first();
    }

}
