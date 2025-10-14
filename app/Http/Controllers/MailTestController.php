<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Abstracts\Controller;
use App\Mail\BudgetNotificationMail;
use App\Mail\EmailVerificationMail;
use App\Models\Budget;
use App\Models\Customer;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Infrastructure\MailerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Controller para testes das novas funcionalidades de e-mail.
 *
 * Este controller fornece endpoints para testar as novas Mailables
 * e Notifications implementadas no sistema Easy Budget Laravel.
 */
class MailTestController extends Controller
{
    /**
     * Testa o envio de e-mail de verificação.
     */
    public function testEmailVerification( Request $request )
    {
        try {
            $user = Auth::user();
            if ( !$user ) {
                return response()->json( [ 'error' => 'Usuário não autenticado' ], 401 );
            }

            $tenant = $user->tenant ?? Tenant::first();

            // Dados de teste
            $verificationToken = 'test_verification_token_' . time();
            $verificationUrl   = config( 'app.url' ) . '/confirm-account?token=' . $verificationToken;

            // Usar o serviço de e-mail
            $mailerService = app( MailerService::class);
            $result        = $mailerService->sendEmailVerificationMail(
                $user,
                $verificationToken,
                $tenant,
                [ 'company_name' => $tenant?->name ?? 'Easy Budget' ],
                $verificationUrl,
                'pt-BR',
            );

            if ( $result->isSuccess() ) {
                return response()->json( [
                    'success' => true,
                    'message' => 'E-mail de verificação enviado com sucesso',
                    'data'    => $result->getData(),
                ] );
            } else {
                return response()->json( [
                    'success' => false,
                    'error'   => $result->getMessage(),
                ], 500 );
            }

        } catch ( \Exception $e ) {
            return response()->json( [
                'success' => false,
                'error'   => 'Erro interno: ' . $e->getMessage(),
            ], 500 );
        }
    }

    /**
     * Testa o envio de notificação de orçamento.
     */
    public function testBudgetNotification( Request $request )
    {
        try {
            $user = Auth::user();
            if ( !$user ) {
                return response()->json( [ 'error' => 'Usuário não autenticado' ], 401 );
            }

            $tenant   = $user->tenant ?? Tenant::first();
            $customer = Customer::where( 'tenant_id', $tenant?->id ?? 1 )->first();

            if ( !$customer ) {
                return response()->json( [ 'error' => 'Cliente de teste não encontrado' ], 404 );
            }

            // Criar orçamento de teste
            $budget = Budget::factory()->create( [
                'tenant_id'   => $tenant?->id ?? 1,
                'customer_id' => $customer->id,
                'code'        => 'TEST-' . time(),
                'total'       => 1500.00,
                'discount'    => 50.00,
                'description' => 'Orçamento de teste para funcionalidades de e-mail',
            ] );

            // Usar o serviço de e-mail
            $mailerService = app( MailerService::class);
            $result        = $mailerService->sendBudgetNotificationMail(
                $budget,
                $customer,
                'created',
                $tenant,
                [ 'company_name' => $tenant?->name ?? 'Easy Budget' ],
                config( 'app.url' ) . '/budgets/' . $budget->id,
                'Este é um orçamento de teste das novas funcionalidades de e-mail.',
                'pt-BR',
            );

            if ( $result->isSuccess() ) {
                return response()->json( [
                    'success' => true,
                    'message' => 'Notificação de orçamento enviada com sucesso',
                    'data'    => $result->getData(),
                ] );
            } else {
                return response()->json( [
                    'success' => false,
                    'error'   => $result->getMessage(),
                ], 500 );
            }

        } catch ( \Exception $e ) {
            return response()->json( [
                'success' => false,
                'error'   => 'Erro interno: ' . $e->getMessage(),
            ], 500 );
        }
    }

    /**
     * Testa o envio de notificação de fatura aprimorada.
     */
    public function testInvoiceNotification( Request $request )
    {
        try {
            $user = Auth::user();
            if ( !$user ) {
                return response()->json( [ 'error' => 'Usuário não autenticado' ], 401 );
            }

            $tenant   = $user->tenant ?? Tenant::first();
            $customer = Customer::where( 'tenant_id', $tenant?->id ?? 1 )->first();

            if ( !$customer ) {
                return response()->json( [ 'error' => 'Cliente de teste não encontrado' ], 404 );
            }

            // Criar fatura de teste
            $invoice = \App\Models\Invoice::factory()->create( [
                'tenant_id'      => $tenant?->id ?? 1,
                'customer_id'    => $customer->id,
                'code'           => 'TEST-INV-' . time(),
                'total'          => 1200.00,
                'subtotal'       => 1000.00,
                'discount'       => 100.00,
                'due_date'       => now()->addDays( 30 ),
                'payment_method' => 'PIX',
                'notes'          => 'Fatura de teste para funcionalidades de e-mail',
            ] );

            // Usar o serviço de e-mail
            $mailerService = app( MailerService::class);
            $result        = $mailerService->sendEnhancedInvoiceNotification(
                $invoice,
                $customer,
                $tenant,
                [ 'company_name' => $tenant?->name ?? 'Easy Budget' ],
                config( 'app.url' ) . '/invoice/' . $invoice->public_hash,
                'Esta é uma fatura de teste das funcionalidades aprimoradas de e-mail.',
                'pt-BR',
            );

            if ( $result->isSuccess() ) {
                return response()->json( [
                    'success' => true,
                    'message' => 'Notificação de fatura enviada com sucesso',
                    'data'    => $result->getData(),
                ] );
            } else {
                return response()->json( [
                    'success' => false,
                    'error'   => $result->getMessage(),
                ], 500 );
            }

        } catch ( \Exception $e ) {
            return response()->json( [
                'success' => false,
                'error'   => 'Erro interno: ' . $e->getMessage(),
            ], 500 );
        }
    }

    /**
     * Lista estatísticas da fila de e-mails.
     */
    public function getEmailQueueStats( Request $request )
    {
        try {
            $mailerService = app( MailerService::class);
            $stats         = $mailerService->getEmailQueueStats();

            return response()->json( [
                'success' => true,
                'data'    => $stats,
            ] );

        } catch ( \Exception $e ) {
            return response()->json( [
                'success' => false,
                'error'   => 'Erro interno: ' . $e->getMessage(),
            ], 500 );
        }
    }

}
