<?php

namespace Tests\Unit;

use App\Contracts\Interfaces\StatusEnumInterface;
use App\Enums\BudgetStatus;
use App\Enums\InvoiceStatus;
use App\Enums\OperationStatus;
use App\Enums\ServiceStatus;
use App\Enums\SupportStatus;
use App\Enums\TokenType;
use Tests\TestCase;

class EnumInterfaceComplianceTest extends TestCase
{
    /** @test */
    public function budget_status_implements_interface(): void
    {
        $enum = BudgetStatus::DRAFT;

        $this->assertInstanceOf( StatusEnumInterface::class, $enum );

        // Verificar todos os métodos da interface
        $this->assertIsString( $enum->getDescription() );
        $this->assertIsString( $enum->getColor() );
        $this->assertIsString( $enum->getIcon() );
        $this->assertIsBool( $enum->isActive() );
        $this->assertIsBool( $enum->isFinished() );
        $this->assertIsArray( $enum->getMetadata() );
        $this->assertInstanceOf( StatusEnumInterface::class, BudgetStatus::fromString( 'draft' ) );
        $this->assertIsArray( BudgetStatus::getOptions() );
        $this->assertIsArray( BudgetStatus::getOrdered() );
        $this->assertIsArray( BudgetStatus::calculateMetrics( [ BudgetStatus::DRAFT ] ) );

        // Teste específico do BudgetStatus
        $this->assertTrue( $enum->canEdit() );
        $this->assertTrue( $enum->canDelete() );
        $this->assertTrue( $enum->canTransitionTo( BudgetStatus::PENDING ) );
    }

    /** @test */
    public function invoice_status_implements_interface(): void
    {
        $enum = InvoiceStatus::PENDING;

        $this->assertInstanceOf( StatusEnumInterface::class, $enum );

        // Verificar todos os métodos da interface
        $this->assertIsString( $enum->getDescription() );
        $this->assertIsString( $enum->getColor() );
        $this->assertIsString( $enum->getIcon() );
        $this->assertIsBool( $enum->isActive() );
        $this->assertIsBool( $enum->isFinished() );
        $this->assertIsArray( $enum->getMetadata() );
        $this->assertInstanceOf( StatusEnumInterface::class, InvoiceStatus::fromString( 'PENDING' ) );
        $this->assertIsArray( InvoiceStatus::getOptions() );
        $this->assertIsArray( InvoiceStatus::getOrdered() );
        $this->assertIsArray( InvoiceStatus::calculateMetrics( [ InvoiceStatus::PENDING ] ) );
    }

    /** @test */
    public function service_status_implements_interface(): void
    {
        $enum = ServiceStatus::DRAFT;

        $this->assertInstanceOf( StatusEnumInterface::class, $enum );

        // Verificar todos os métodos da interface
        $this->assertIsString( $enum->getDescription() );
        $this->assertIsString( $enum->getColor() );
        $this->assertIsString( $enum->getIcon() );
        $this->assertIsBool( $enum->isActive() );
        $this->assertIsBool( $enum->isFinished() );
        $this->assertIsArray( $enum->getMetadata() );
        $this->assertInstanceOf( StatusEnumInterface::class, ServiceStatus::fromString( 'DRAFT' ) );
        $this->assertIsArray( ServiceStatus::getOptions() );
        $this->assertIsArray( ServiceStatus::getOrdered() );
        $this->assertIsArray( ServiceStatus::calculateMetrics( [ ServiceStatus::DRAFT ] ) );
    }

    /** @test */
    public function support_status_implements_interface(): void
    {
        $enum = SupportStatus::ABERTO;

        $this->assertInstanceOf( StatusEnumInterface::class, $enum );

        // Verificar todos os métodos da interface
        $this->assertIsString( $enum->getDescription() );
        $this->assertIsString( $enum->getColor() );
        $this->assertIsString( $enum->getIcon() );
        $this->assertIsBool( $enum->isActive() );
        $this->assertIsBool( $enum->isFinished() );
        $this->assertIsArray( $enum->getMetadata() );
        $this->assertInstanceOf( StatusEnumInterface::class, SupportStatus::fromString( 'ABERTO' ) );
        $this->assertIsArray( SupportStatus::getOptions() );
        $this->assertIsArray( SupportStatus::getOrdered() );
        $this->assertIsArray( SupportStatus::calculateMetrics( [ SupportStatus::ABERTO ] ) );
    }

    /** @test */
    public function operation_status_implements_interface(): void
    {
        $enum = OperationStatus::SUCCESS;

        $this->assertInstanceOf( StatusEnumInterface::class, $enum );

        // Verificar todos os métodos da interface
        $this->assertIsString( $enum->getDescription() );
        $this->assertIsString( $enum->getColor() );
        $this->assertIsString( $enum->getIcon() );
        $this->assertIsBool( $enum->isActive() );
        $this->assertIsBool( $enum->isFinished() );
        $this->assertIsArray( $enum->getMetadata() );
        $this->assertInstanceOf( StatusEnumInterface::class, OperationStatus::fromString( 'success' ) );
        $this->assertIsArray( OperationStatus::getOptions() );
        $this->assertIsArray( OperationStatus::getOrdered() );
        $this->assertIsArray( OperationStatus::calculateMetrics( [ OperationStatus::SUCCESS ] ) );

        // Testes específicos do OperationStatus
        $this->assertTrue( $enum->isSuccess() );
        $this->assertFalse( $enum->isError() );
    }

    /** @test */
    public function token_type_implements_interface(): void
    {
        $enum = TokenType::EMAIL_VERIFICATION;

        $this->assertInstanceOf( StatusEnumInterface::class, $enum );

        // Verificar todos os métodos da interface
        $this->assertIsString( $enum->getDescription() );
        $this->assertIsString( $enum->getColor() );
        $this->assertIsString( $enum->getIcon() );
        $this->assertIsBool( $enum->isActive() );
        $this->assertIsBool( $enum->isFinished() );
        $this->assertIsArray( $enum->getMetadata() );
        $this->assertInstanceOf( StatusEnumInterface::class, TokenType::fromString( 'email_verification' ) );
        $this->assertIsArray( TokenType::getOptions() );
        $this->assertIsArray( TokenType::getOrdered() );
        $this->assertIsArray( TokenType::calculateMetrics( [ TokenType::EMAIL_VERIFICATION ] ) );

        // Testes específicos do TokenType
        $this->assertIsInt( $enum->getDefaultExpirationMinutes() );
        $this->assertTrue( TokenType::isValid( 'email_verification' ) );
        $this->assertFalse( TokenType::isValid( 'invalid_type' ) );
    }

}
