<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create( 'plan_subscriptions', function (Blueprint $table) {
            $table->id(); // ID primário da assinatura do plano

            // tenant_id: ID do tenant associado à assinatura, com índice para consultas rápidas
            $table->unsignedBigInteger( 'tenant_id' );
            $table->index( 'tenant_id' );
            $table->foreign( 'tenant_id' )
                ->references( 'id' )
                ->on( 'tenants' )
                ->onDelete( 'cascade' )
                ->comment( 'Chave estrangeira para a tabela tenants, cascateia exclusão' );

            // plan_id: ID do plano associado à assinatura, com índice
            $table->unsignedBigInteger( 'plan_id' );
            $table->index( 'plan_id' );
            $table->foreign( 'plan_id' )
                ->references( 'id' )
                ->on( 'plans' )
                ->onDelete( 'restrict' )
                ->comment( 'Chave estrangeira para a tabela plans, restringe exclusão' );

            // user_id: ID do usuário associado à assinatura, com índice (adicionado conforme requisito)
            $table->unsignedBigInteger( 'user_id' );
            $table->index( 'user_id' );
            $table->foreign( 'user_id' )
                ->references( 'id' )
                ->on( 'users' )
                ->onDelete( 'cascade' )
                ->comment( 'Chave estrangeira para a tabela users, cascateia exclusão' );

            // provider_id: ID do provedor associado à assinatura (do modelo), nullable, com índice
            $table->unsignedBigInteger( 'provider_id' )->nullable();
            $table->index( 'provider_id' );
            $table->foreign( 'provider_id' )
                ->references( 'id' )
                ->on( 'providers' )
                ->onDelete( 'set null' )
                ->comment( 'Chave estrangeira para a tabela providers, define null em exclusão' );

            // start_date: Data de início da assinatura
            $table->date( 'start_date' )
                ->comment( 'Data de início da vigência da assinatura do plano' );

            // end_date: Data de término da assinatura, nullable
            $table->date( 'end_date' )->nullable()
                ->comment( 'Data de término da vigência da assinatura do plano, nula se indefinida' );

            // status: Status da assinatura, enum com valores padrão
            $table->enum( 'status', [ 'active', 'expired', 'cancelled', 'suspended' ] )
                ->default( 'active' );
            $table->index( 'status' );
            $table->comment( 'Status da assinatura: active (ativa), expired (expirada), cancelled (cancelada), suspended (suspensa)' );

            // transaction_amount: Valor da transação, decimal
            $table->decimal( 'transaction_amount', 10, 2 )->nullable()
                ->comment( 'Valor total da transação de assinatura' );

            // transaction_date: Data da transação
            $table->dateTime( 'transaction_date' )->nullable()
                ->comment( 'Data e hora da transação de pagamento' );

            // payment_method: Método de pagamento utilizado
            $table->string( 'payment_method' )->nullable()
                ->comment( 'Método de pagamento, ex: credit_card, boleto' );

            // payment_id: ID do pagamento no gateway, string nullable
            $table->string( 'payment_id' )->nullable()
                ->comment( 'Identificador único do pagamento no provedor externo' );

            // public_hash: Hash público para referência da assinatura
            $table->string( 'public_hash' )->unique()
                ->comment( 'Hash único público para identificação externa da assinatura' );

            // last_payment_date: Data do último pagamento
            $table->dateTime( 'last_payment_date' )->nullable()
                ->comment( 'Data e hora do último pagamento realizado' );

            // next_payment_date: Data do próximo pagamento esperado
            $table->dateTime( 'next_payment_date' )->nullable()
                ->comment( 'Data e hora prevista para o próximo pagamento/renovação' );

            $table->timestamps(); // created_at e updated_at automáticos
        } );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists( 'plan_subscriptions' );
    }

};
