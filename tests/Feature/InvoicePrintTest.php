<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoicePrintTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_print_route_works()
    {
        // Create a user and invoice
        $user    = User::factory()->create();
        $invoice = Invoice::factory()->create( [ 'tenant_id' => $user->tenant_id ] );

        // Act as the user
        $this->actingAs( $user );

        // Test the print route
        $response = $this->get( route( 'provider.invoices.print', $invoice->code ) );

        // Assert the response is successful
        $response->assertStatus( 200 );

        // Assert the view is returned
        $response->assertViewIs( 'pages.invoice.print' );

        // Assert the invoice data is passed to the view
        $response->assertViewHas( 'invoice', function ( $viewInvoice ) use ( $invoice ) {
            return $viewInvoice->id === $invoice->id;
        } );
    }

    public function test_invoice_print_route_requires_authentication()
    {
        // Create an invoice
        $invoice = Invoice::factory()->create();

        // Test the print route without authentication
        $response = $this->get( route( 'provider.invoices.print', $invoice->code ) );

        // Assert the user is redirected to login
        $response->assertRedirect( route( 'login' ) );
    }

    public function test_invoice_print_route_requires_correct_tenant()
    {
        // Create users and invoices with different tenants
        $user1   = User::factory()->create( [ 'tenant_id' => 1 ] );
        $user2   = User::factory()->create( [ 'tenant_id' => 2 ] );
        $invoice = Invoice::factory()->create( [ 'tenant_id' => 1 ] );

        // Act as user2 and try to access user1's invoice
        $this->actingAs( $user2 );

        // Test the print route
        $response = $this->get( route( 'provider.invoices.print', $invoice->code ) );

        // Assert the response is not found (404)
        $response->assertStatus( 404 );
    }

}
