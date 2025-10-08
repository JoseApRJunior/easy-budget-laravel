<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Auth;

class TinyMceService
{
    /**
     * Obtém configuração completa do TinyMCE.
     */
    public function getConfiguration(): array
    {
        $user = Auth::user();

        return [
            'selector'                      => 'textarea.tinymce',
            'height'                        => 500,
            'min_height'                    => 400,
            'max_height'                    => 800,
            'resize'                        => true,
            'menubar'                       => true,
            'statusbar'                     => true,
            'elementpath'                   => false,
            'branding'                      => false,
            'promotion'                     => false,
            'plugins'                       => $this->getPlugins(),
            'toolbar'                       => $this->getToolbar(),
            'toolbar_mode'                  => 'sliding',
            'toolbar_sticky'                => true,
            'contextmenu'                   => 'link image table configurepermanentpen',
            'image_advtab'                  => true,
            'image_title'                   => true,
            'automatic_uploads'             => true,
            'file_picker_types'             => 'image media',
            'images_upload_handler'         => 'function (blobInfo, success, failure, progress) { uploadImage(blobInfo, success, failure, progress); }',
            'content_style'                 => $this->getContentStyle(),
            'body_class'                    => 'email-content',
            'body_id'                       => 'emailTemplate',
            'setup'                         => 'function (editor) { ' . $this->getSetupCallback() . ' }',
            'init_instance_callback'        => 'function (editor) { editor.on("change", function () { editor.save(); updatePreview(); }); }',
            'paste_data_images'             => true,
            'paste_as_text'                 => false,
            'paste_auto_cleanup_on_paste'   => true,
            'paste_remove_styles'           => false,
            'paste_remove_styles_if_webkit' => false,
            'smart_paste'                   => true,
            'link_context_toolbar'          => true,
            'image_context_toolbar'         => true,
            'table_default_attributes'      => [
                'class' => 'table table-bordered'
            ],
            'table_default_styles'          => [
                'border-collapse' => 'collapse',
                'width'           => '100%'
            ],
            'table_responsive_width'        => true,
            'table_resize_bars'             => true,
            'table_resize_handles'          => 'cursor',
            'visual_table_properties'       => true,
            'table_toolbar'                 => 'tableprops tabledelete | tableinsertrowbefore tableinsertrowafter tabledeleterow | tableinsertcolbefore tableinsertcolafter tabledeletecol',
            'language'                      => 'pt_BR',
            'directionality'                => 'ltr',
            'spellchecker_language'         => 'pt_BR',
            'spellchecker_rpc_url'          => '/spellchecker',
        ];
    }

    /**
     * Obtém lista de plugins habilitados.
     */
    private function getPlugins(): string
    {
        return implode( ' ', [
            'advlist',
            'autolink',
            'lists',
            'link',
            'image',
            'charmap',
            'preview',
            'anchor',
            'searchreplace',
            'visualblocks',
            'code',
            'fullscreen',
            'insertdatetime',
            'media',
            'table',
            'help',
            'wordcount',
            'emoticons',
            'template',
            'codesample',
            'hr',
            'pagebreak',
            'nonbreaking',
            'toc',
            'imagetools',
            'textpattern',
            'paste',
            'importcss',
            'autosave',
            'save',
            'directionality',
            'visualchars',
            'quickbars',
        ] );
    }

    /**
     * Obtém configuração da toolbar.
     */
    private function getToolbar(): string
    {
        return implode( ' | ', [
            'undo redo',
            'blocks fontfamily fontsize',
            'bold italic underline strikethrough',
            'alignleft aligncenter alignright alignjustify',
            'bullist numlist outdent indent',
            'forecolor backcolor',
            'link image media table',
            'hr pagebreak',
            'template codesample',
            'preview fullscreen',
            'code',
        ] );
    }

    /**
     * Obtém estilos CSS para o conteúdo.
     */
    private function getContentStyle(): string
    {
        return '
            body {
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                font-size: 14px;
                line-height: 1.6;
                color: #333333;
                max-width: 600px;
                margin: 0 auto;
                padding: 20px;
                background: #ffffff;
            }
            .email-content {
                font-family: inherit;
            }
            table {
                border-collapse: collapse;
                width: 100%;
                margin: 20px 0;
            }
            table td, table th {
                border: 1px solid #dddddd;
                padding: 8px;
                text-align: left;
            }
            table th {
                background-color: #f5f5f5;
                font-weight: bold;
            }
            .btn {
                display: inline-block;
                padding: 12px 24px;
                background-color: #007bff;
                color: white;
                text-decoration: none;
                border-radius: 4px;
                margin: 10px 0;
            }
            .highlight {
                background-color: #f8f9fa;
                padding: 15px;
                border-radius: 4px;
                margin: 20px 0;
                border-left: 4px solid #007bff;
            }
        ';
    }

    /**
     * Obtém callback de configuração do editor.
     */
    private function getSetupCallback(): string
    {
        return '
            editor.ui.registry.addButton("variable", {
                text: "Variável",
                onAction: function () {
                    showVariableSelector();
                }
            });

            editor.on("init", function () {
                // Adicionar estilos customizados
                editor.getDoc().body.style.fontFamily = "Arial, sans-serif";
                editor.getDoc().body.style.fontSize = "14px";
            });

            editor.on("paste", function (e) {
                // Limpar estilos indesejados do paste
                setTimeout(function() {
                    var content = editor.getContent();
                    content = content.replace(/style="[^"]*mso-[^"]*"/gi, "");
                    content = content.replace(/class="[^"]*Mso[^"]*"/gi, "");
                    content = content.replace(/<!\[[^>]*\]>/gi, "");
                    editor.setContent(content);
                }, 100);
            });
        ';
    }

    /**
     * Obtém configuração mínima para usos específicos.
     */
    public function getMinimalConfiguration(): array
    {
        $config            = $this->getConfiguration();
        $config[ 'height' ]  = 300;
        $config[ 'toolbar' ] = 'bold italic | alignleft aligncenter alignright | bullist numlist | link';
        $config[ 'plugins' ] = 'lists link paste';

        return $config;
    }

    /**
     * Obtém configuração para dispositivos móveis.
     */
    public function getMobileConfiguration(): array
    {
        $config            = $this->getConfiguration();
        $config[ 'height' ]  = 400;
        $config[ 'plugins' ] = 'lists link image paste';
        $config[ 'toolbar' ] = 'undo redo | bold italic | alignleft aligncenter alignright | bullist numlist | link image';

        return $config;
    }

    /**
     * Obtém templates predefinidos para o editor.
     */
    public function getEditorTemplates(): array
    {
        return [
            [
                'title'       => 'Cabeçalho com Logo',
                'description' => 'Cabeçalho de email com logo da empresa',
                'content'     => '
                    <div style="text-align: center; padding: 20px; background: #f8f9fa; border-radius: 5px 5px 0 0;">
                        <img src="{{company_logo}}" alt="{{company_name}}" style="max-height: 60px;">
                        <h1 style="margin: 10px 0 0 0; color: #333;">{{company_name}}</h1>
                    </div>
                '
            ],
            [
                'title'       => 'Seção de Destaque',
                'description' => 'Bloco destacado para informações importantes',
                'content'     => '
                    <div class="highlight" style="background: #e7f3ff; padding: 20px; border-radius: 5px; border-left: 4px solid #007bff; margin: 20px 0;">
                        <h3 style="margin-top: 0; color: #007bff;">{{title}}</h3>
                        <p style="margin-bottom: 0;">{{content}}</p>
                    </div>
                '
            ],
            [
                'title'       => 'Botão de Ação',
                'description' => 'Botão estilizado para ações',
                'content'     => '
                    <div style="text-align: center; margin: 30px 0;">
                        <a href="{{link}}" class="btn" style="display: inline-block; padding: 12px 24px; background: #28a745; color: white; text-decoration: none; border-radius: 5px; font-weight: bold;">
                            {{button_text}}
                        </a>
                    </div>
                '
            ],
            [
                'title'       => 'Rodapé Padrão',
                'description' => 'Rodapé com informações de contato',
                'content'     => '
                    <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #dee2e6; font-size: 12px; color: #6c757d; text-align: center;">
                        <p>{{company_name}}</p>
                        <p>{{company_email}} | {{company_phone}}</p>
                        <p>Este é um email automático, por favor não responda diretamente.</p>
                    </div>
                '
            ],
        ];
    }

    /**
     * Obtém configuração para modo escuro.
     */
    public function getDarkModeConfiguration(): array
    {
        $config                = $this->getConfiguration();
        $config[ 'skin' ]        = 'oxide-dark';
        $config[ 'content_css' ] = 'dark';

        return $config;
    }

    /**
     * Valida se o conteúdo HTML é válido para email.
     */
    public function validateEmailContent( string $content ): array
    {
        $errors   = [];
        $warnings = [];

        // Verificar se há variáveis não substituídas
        preg_match_all( '/\{\{(\w+)\}\}/', $content, $matches );
        if ( !empty( $matches[ 1 ] ) ) {
            $warnings[] = 'Variáveis encontradas no conteúdo: ' . implode( ', ', $matches[ 1 ] );
        }

        // Verificar se há elementos não suportados por clientes de email
        if ( strpos( $content, '<script' ) !== false ) {
            $errors[] = 'Scripts não são suportados na maioria dos clientes de email';
        }

        if ( strpos( $content, '<iframe' ) !== false ) {
            $warnings[] = 'iframes podem não funcionar em alguns clientes de email';
        }

        // Verificar se há estilos externos
        if ( preg_match( '/<link[^>]*rel=["\']stylesheet["\'][^>]*>/i', $content ) ) {
            $warnings[] = 'Folhas de estilo externas podem não carregar em alguns clientes';
        }

        return [
            'valid'    => empty( $errors ),
            'errors'   => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * Converte HTML para texto plano preservando formatação básica.
     */
    public function htmlToText( string $html ): string
    {
        // Remover tags HTML mantendo conteúdo
        $text = strip_tags( $html );

        // Decodificar entidades HTML
        $text = html_entity_decode( $text, ENT_QUOTES, 'UTF-8' );

        // Remover múltiplos espaços
        $text = preg_replace( '/\s+/', ' ', $text );

        // Remover espaços no início e fim
        $text = trim( $text );

        // Adicionar quebras de linha para melhor legibilidade
        $text = preg_replace( '/\.(?=\S)/', ".\n", $text );

        return $text;
    }

    /**
     * Obtém estatísticas de uso do editor.
     */
    public function getUsageStats(): array
    {
        return [
            'version'       => '6.8.2',
            'features'      => [
                'plugins_count'   => count( explode( ' ', $this->getPlugins() ) ),
                'toolbar_items'   => count( explode( ' | ', $this->getToolbar() ) ),
                'templates_count' => count( $this->getEditorTemplates() ),
            ],
            'configuration' => [
                'default_height' => 500,
                'max_height'     => 800,
                'language'       => 'pt_BR',
                'responsive'     => true,
            ],
        ];
    }

}
