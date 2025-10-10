<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Helper\ProgressBar;

/**
 * Comando para migrar estrutura de serviços para organização por camadas.
 *
 * Este comando automatiza a reorganização dos serviços seguindo os princípios
 * de Clean Architecture e Domain-Driven Design.
 *
 * @author Kilo Code - Arquiteto de Software
 * @version 1.0.0
 */
class MigrateServicesStructure extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'services:migrate-structure
                            {--dry-run : Executa em modo simulação sem alterar arquivos}
                            {--backup : Cria backup dos arquivos antes da migração}
                            {--force : Força execução mesmo com arquivos existentes}';

    /**
     * The console command description.
     */
    protected $description = 'Migra estrutura de serviços para organização por camadas (Domain, Application, Infrastructure)';

    /**
     * Estrutura de destino organizada por camadas.
     */
    private array $targetStructure = [
        'Domain'         => [
            'description' => 'Serviços de Domínio (Model-based) - CRUD e regras de negócio',
            'services'    => [
                'ActivityService.php',
                'AddressService.php',
                'AuditService.php',
                'BudgetService.php',
                'CategoryService.php',
                'CommonDataService.php',
                'ContactService.php',
                'CustomerService.php',
                'InvoiceService.php',
                'PlanService.php',
                'ProductService.php',
                'ProviderService.php',
                'ReportService.php',
                'RoleService.php',
                'ServiceService.php',
                'SettingsService.php',
                'SupportService.php',
                'UserService.php',
            ]
        ],
        'Application'    => [
            'description' => 'Serviços de Aplicação (Business Logic) - Coordenação complexa',
            'services'    => [
                'BudgetCalculationService.php',
                'BudgetPdfService.php',
                'BudgetStatusService.php',
                'BudgetTemplateService.php',
                'CustomerInteractionService.php',
                'EmailTemplateService.php',
                'EmailTrackingService.php',
                'ExportService.php',
                'FileUploadService.php',
                'InvoiceStatusService.php',
                'ProviderManagementService.php',
                'ServiceStatusService.php',
                'SettingsBackupService.php',
                'UserRegistrationService.php',
            ]
        ],
        'Infrastructure' => [
            'description' => 'Serviços de Infraestrutura (External Services) - APIs e integrações',
            'services'    => [
                'CacheService.php',
                'ChartService.php',
                'ChartVisualizationService.php',
                'EncryptionService.php',
                'FinancialSummary.php',
                'GeolocationService.php',
                'MailerService.php',
                'MercadoPagoService.php',
                'MerchantOrderMercadoPagoService.php',
                'MetricsService.php',
                'NotificationService.php',
                'PaymentMercadoPagoInvoiceService.php',
                'PaymentMercadoPagoPlanService.php',
                'PaymentService.php',
                'PdfService.php',
                'VariableProcessor.php',
                'WebhookService.php',
            ]
        ],
        'Core'           => [
            'description' => 'Arquitetura Core (Abstrações) - Classes base e contratos',
            'services'    => [
                // Será criado automaticamente - contém Abstracts, Contracts, Traits
            ]
        ],
        'Shared'         => [
            'description' => 'Serviços Compartilhados - Utilitários comuns',
            'services'    => [
                // Será criado automaticamente - serviços que podem ser reutilizados
            ]
        ]
    ];

    /**
     * Regras de categorização automática baseadas em padrões.
     */
    private array $autoCategorizationRules = [
        'Domain'         => [
            'patterns' => [ '*Service.php' ], // Padrão geral para serviços
            'keywords' => [ 'Customer', 'Product', 'Budget', 'User', 'Role', 'Category', 'Audit', 'Activity' ],
            'exclude'  => [ 'Calculation', 'Pdf', 'Template', 'Status', 'Management', 'Tracking', 'Export', 'Upload', 'Backup' ]
        ],
        'Application'    => [
            'patterns' => [ '*CalculationService.php', '*PdfService.php', '*TemplateService.php', '*StatusService.php' ],
            'keywords' => [ 'Calculation', 'Pdf', 'Template', 'Status', 'Management', 'Tracking', 'Export', 'Upload', 'Backup', 'Registration', 'Interaction' ],
            'exclude'  => [ 'MercadoPago', 'Cache', 'Chart', 'Mail', 'Payment' ]
        ],
        'Infrastructure' => [
            'patterns' => [ '*MercadoPago*.php', '*Cache*.php', '*Chart*.php', '*Mail*.php', '*Payment*.php', '*Pdf*.php' ],
            'keywords' => [ 'MercadoPago', 'Cache', 'Chart', 'Mail', 'Payment', 'Pdf', 'Encryption', 'Geolocation', 'Metrics', 'Notification', 'VariableProcessor', 'Webhook' ],
            'exclude'  => []
        ]
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info( '🏗️  Migração de Estrutura de Serviços - Easy Budget Laravel' );
        $this->info( '============================================================' );

        // Verificar modo de execução
        $isDryRun     = $this->option( 'dry-run' );
        $createBackup = $this->option( 'backup' );
        $force        = $this->option( 'force' );

        if ( $isDryRun ) {
            $this->warn( '⚠️  MODO SIMULAÇÃO ATIVO - Nenhuma alteração será feita' );
        }

        if ( $createBackup && !$isDryRun ) {
            $this->info( '💾 Criando backup dos arquivos...' );
            $this->createBackup();
        }

        // Analisar serviços atuais
        $currentServices = $this->analyzeCurrentServices();

        if ( empty( $currentServices ) ) {
            $this->error( '❌ Nenhum serviço encontrado em app/Services/' );
            return 1;
        }

        $this->info( "📊 Encontrados {$currentServices[ 'count' ]} serviços para migrar" );

        // Categorizar serviços automaticamente
        $categorizedServices = $this->categorizeServicesAutomatically( $currentServices[ 'files' ] );

        // Mostrar plano de migração
        $this->displayMigrationPlan( $categorizedServices );

        if ( !$this->confirm( 'Deseja prosseguir com a migração?', !$isDryRun ) ) {
            $this->info( '✅ Migração cancelada pelo usuário' );
            return 0;
        }

        // Executar migração
        $results = $this->executeMigration( $categorizedServices, $isDryRun, $force );

        // Mostrar resultados
        $this->displayResults( $results );

        // Gerar relatório
        if ( !$isDryRun ) {
            $this->generateMigrationReport( $results );
        }

        return 0;
    }

    /**
     * Analisa serviços atualmente na pasta app/Services/.
     */
    private function analyzeCurrentServices(): array
    {
        $servicesPath = app_path( 'Services' );
        $files        = File::files( $servicesPath );

        $serviceFiles = [];
        foreach ( $files as $file ) {
            if ( $file->getExtension() === 'php' && str_contains( $file->getFilename(), 'Service.php' ) ) {
                $serviceFiles[] = $file->getFilename();
            }
        }

        return [
            'path'  => $servicesPath,
            'files' => $serviceFiles,
            'count' => count( $serviceFiles )
        ];
    }

    /**
     * Categoriza serviços automaticamente baseado em regras.
     */
    private function categorizeServicesAutomatically( array $serviceFiles ): array
    {
        $categorized = [];

        foreach ( $this->targetStructure as $layer => $config ) {
            $categorized[ $layer ] = [
                'services'         => [],
                'auto_categorized' => [],
                'manual_override'  => []
            ];
        }

        foreach ( $serviceFiles as $serviceFile ) {
            $categorizedService = $this->categorizeSingleService( $serviceFile );

            if ( $categorizedService ) {
                $categorized[ $categorizedService ][ 'services' ][]         = $serviceFile;
                $categorized[ $categorizedService ][ 'auto_categorized' ][] = $serviceFile;
            }
        }

        return $categorized;
    }

    /**
     * Categoriza um único serviço baseado em regras automáticas.
     */
    private function categorizeSingleService( string $serviceFile ): ?string
    {
        // Verificar regras específicas primeiro
        foreach ( $this->autoCategorizationRules as $layer => $rules ) {
            // Verificar padrões
            foreach ( $rules[ 'patterns' ] as $pattern ) {
                if ( fnmatch( $pattern, $serviceFile ) ) {
                    // Verificar se não está na lista de exclusão
                    $excluded = false;
                    foreach ( $rules[ 'exclude' ] as $exclude ) {
                        if ( str_contains( $serviceFile, $exclude ) ) {
                            $excluded = true;
                            break;
                        }
                    }
                    if ( !$excluded ) {
                        return $layer;
                    }
                }
            }

            // Verificar keywords
            foreach ( $rules[ 'keywords' ] as $keyword ) {
                if ( str_contains( $serviceFile, $keyword ) ) {
                    // Verificar se não está na lista de exclusão
                    $excluded = false;
                    foreach ( $rules[ 'exclude' ] as $exclude ) {
                        if ( str_contains( $serviceFile, $exclude ) ) {
                            $excluded = true;
                            break;
                        }
                    }
                    if ( !$excluded ) {
                        return $layer;
                    }
                }
            }
        }

        // Fallback: tentar categorizar baseado no nome
        return $this->categorizeByName( $serviceFile );
    }

    /**
     * Categoriza baseado no nome do arquivo.
     */
    private function categorizeByName( string $serviceFile ): ?string
    {
        $name = strtolower( str_replace( 'Service.php', '', $serviceFile ) );

        // Padrões comuns para cada camada
        $domainPatterns         = [ 'customer', 'product', 'budget', 'user', 'role', 'category', 'audit', 'activity', 'address', 'contact', 'invoice', 'plan', 'provider', 'report', 'service', 'settings', 'support' ];
        $applicationPatterns    = [ 'calculation', 'template', 'status', 'management', 'tracking', 'export', 'upload', 'backup', 'registration', 'interaction' ];
        $infrastructurePatterns = [ 'mercadopago', 'cache', 'chart', 'mail', 'payment', 'pdf', 'encryption', 'geolocation', 'metrics', 'notification', 'variable', 'webhook' ];

        if ( $this->matchesAnyPattern( $name, $domainPatterns ) ) {
            return 'Domain';
        }

        if ( $this->matchesAnyPattern( $name, $applicationPatterns ) ) {
            return 'Application';
        }

        if ( $this->matchesAnyPattern( $name, $infrastructurePatterns ) ) {
            return 'Infrastructure';
        }

        // Se não conseguir categorizar automaticamente, deixar para decisão manual
        return null;
    }

    /**
     * Verifica se string corresponde a algum padrão.
     */
    private function matchesAnyPattern( string $string, array $patterns ): bool
    {
        foreach ( $patterns as $pattern ) {
            if ( str_contains( $string, $pattern ) ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Exibe plano de migração.
     */
    private function displayMigrationPlan( array $categorizedServices ): void
    {
        $this->info( "\n📋 PLANO DE MIGRAÇÃO:" );
        $this->info( "=====================" );

        foreach ( $categorizedServices as $layer => $data ) {
            if ( empty( $data[ 'services' ] ) ) {
                continue;
            }

            $this->info( "\n🏗️  {$layer} ({$this->targetStructure[ $layer ][ 'description' ]})" );
            $this->info( "📂 Pasta: app/Services/{$layer}/" );

            foreach ( $data[ 'services' ] as $service ) {
                $this->line( "  ✅ {$service}" );
            }
        }

        $totalServices = array_sum( array_map( fn( $data ) => count( $data[ 'services' ] ), $categorizedServices ) );
        $this->info( "\n📊 Total de serviços a migrar: {$totalServices}" );
    }

    /**
     * Cria estrutura de pastas.
     */
    private function createDirectoryStructure(): void
    {
        $basePath = app_path( 'Services' );

        foreach ( array_keys( $this->targetStructure ) as $layer ) {
            $layerPath = "{$basePath}/{$layer}";
            if ( !File::exists( $layerPath ) ) {
                File::makeDirectory( $layerPath, 0755, true );
                $this->info( "📁 Criada pasta: {$layerPath}" );
            }
        }
    }

    /**
     * Executa a migração dos serviços.
     */
    private function executeMigration( array $categorizedServices, bool $isDryRun, bool $force ): array
    {
        $results = [
            'moved'   => [],
            'errors'  => [],
            'skipped' => []
        ];

        if ( !$isDryRun ) {
            $this->createDirectoryStructure();
        }

        $totalSteps  = array_sum( array_map( fn( $data ) => count( $data[ 'services' ] ), $categorizedServices ) );
        $currentStep = 0;

        foreach ( $categorizedServices as $layer => $data ) {
            foreach ( $data[ 'services' ] as $serviceFile ) {
                $result = $this->migrateSingleService( $serviceFile, $layer, $isDryRun, $force );

                if ( $result[ 'success' ] ) {
                    $results[ 'moved' ][] = [
                        'service' => $serviceFile,
                        'from'    => 'app/Services/' . $serviceFile,
                        'to'      => "app/Services/{$layer}/{$serviceFile}",
                        'layer'   => $layer
                    ];
                } else {
                    $results[ 'errors' ][] = [
                        'service' => $serviceFile,
                        'error'   => $result[ 'error' ]
                    ];
                }

                $currentStep++;
                if ( $currentStep % 5 === 0 ) {
                    $this->output->write( '.' );
                }
            }
        }

        $this->newLine();
        $this->newLine();

        return $results;
    }

    /**
     * Migra um único serviço.
     */
    private function migrateSingleService( string $serviceFile, string $layer, bool $isDryRun, bool $force ): array
    {
        $sourcePath = app_path( "Services/{$serviceFile}" );
        $targetPath = app_path( "Services/{$layer}/{$serviceFile}" );

        // Verificar se arquivo já existe no destino
        if ( File::exists( $targetPath ) && !$force ) {
            return [
                'success' => false,
                'error'   => "Arquivo já existe no destino: {$targetPath}"
            ];
        }

        if ( $isDryRun ) {
            return [ 'success' => true ];
        }

        try {
            // Mover arquivo
            File::move( $sourcePath, $targetPath );

            // Atualizar namespace no arquivo
            $this->updateNamespaceInFile( $targetPath, $layer );

            return [ 'success' => true ];
        } catch ( \Exception $e ) {
            return [
                'success' => false,
                'error'   => $e->getMessage()
            ];
        }
    }

    /**
     * Atualiza namespace no arquivo migrado.
     */
    private function updateNamespaceInFile( string $filePath, string $layer ): void
    {
        $content = File::get( $filePath );

        // Atualizar namespace
        $oldNamespace = 'namespace App\Services;';
        $newNamespace = "namespace App\Services\\{$layer};";

        $content = str_replace( $oldNamespace, $newNamespace, $content );

        // Atualizar imports de outras classes se necessário
        $content = $this->updateImportsInFile( $content, $layer );

        File::put( $filePath, $content );
    }

    /**
     * Atualiza imports no conteúdo do arquivo.
     */
    private function updateImportsInFile( string $content, string $layer ): string
    {
        // Imports que precisam ser ajustados
        $importAdjustments = [
            'App\Services\Abstracts' => "App\Services\\{$layer}\Abstracts",
            'App\Services\Contracts' => "App\Services\\{$layer}\Contracts",
            'App\Services\Traits'    => "App\Services\\{$layer}\Traits",
        ];

        foreach ( $importAdjustments as $oldImport => $newImport ) {
            $content = str_replace( $oldImport, $newImport, $content );
        }

        return $content;
    }

    /**
     * Cria backup dos arquivos.
     */
    private function createBackup(): void
    {
        $backupPath = storage_path( 'app/services-migration-backup-' . date( 'Y-m-d-H-i-s' ) );

        File::copyDirectory( app_path( 'Services' ), $backupPath );

        $this->info( "💾 Backup criado em: {$backupPath}" );
    }

    /**
     * Exibe resultados da migração.
     */
    private function displayResults( array $results ): void
    {
        $this->info( "\n📊 RESULTADOS DA MIGRAÇÃO:" );
        $this->info( "==========================" );

        if ( !empty( $results[ 'moved' ] ) ) {
            $this->info( "✅ Serviços migrados com sucesso: " . count( $results[ 'moved' ] ) );
            foreach ( $results[ 'moved' ] as $moved ) {
                $this->line( "  📂 {$moved[ 'service' ]} → {$moved[ 'layer' ]}/" );
            }
        }

        if ( !empty( $results[ 'errors' ] ) ) {
            $this->error( "❌ Erros durante migração: " . count( $results[ 'errors' ] ) );
            foreach ( $results[ 'errors' ] as $error ) {
                $this->line( "  ⚠️  {$error[ 'service' ]}: {$error[ 'error' ]}" );
            }
        }

        if ( !empty( $results[ 'skipped' ] ) ) {
            $this->warn( "⏭️  Serviços pulados: " . count( $results[ 'skipped' ] ) );
            foreach ( $results[ 'skipped' ] as $skipped ) {
                $this->line( "  ⏭️  {$skipped[ 'service' ]}: {$skipped[ 'reason' ]}" );
            }
        }
    }

    /**
     * Gera relatório da migração.
     */
    private function generateMigrationReport( array $results ): void
    {
        $reportPath = storage_path( 'app/services-migration-report-' . date( 'Y-m-d-H-i-s' ) . '.json' );

        $report = [
            'timestamp'      => date( 'Y-m-d H:i:s' ),
            'moved_services' => $results[ 'moved' ],
            'errors'         => $results[ 'errors' ],
            'skipped'        => $results[ 'skipped' ],
            'summary'        => [
                'total_moved'   => count( $results[ 'moved' ] ),
                'total_errors'  => count( $results[ 'errors' ] ),
                'total_skipped' => count( $results[ 'skipped' ] )
            ]
        ];

        File::put( $reportPath, json_encode( $report, JSON_PRETTY_PRINT ) );

        $this->info( "📋 Relatório gerado em: {$reportPath}" );
    }

}
