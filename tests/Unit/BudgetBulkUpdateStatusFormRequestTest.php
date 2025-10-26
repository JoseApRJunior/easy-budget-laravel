<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\BudgetStatusEnum;
use App\Http\Requests\BudgetBulkUpdateStatusFormRequest;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class BudgetBulkUpdateStatusFormRequestTest extends TestCase
{
    use RefreshDatabase;

    protected $tenant;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->user   = User::factory()->create( [
            'tenant_id' => $this->tenant->id,
        ] );
    }

    /** @test */
    public function it_authorizes_authenticated_users()
    {
        $this->actingAs( $this->user );

        $request = new BudgetBulkUpdateStatusFormRequest();

        $this->assertTrue( $request->authorize() );
    }

    /** @test */
    public function it_validates_required_fields()
    {
        $request = new BudgetBulkUpdateStatusFormRequest();
        $rules   = $request->rules();

        $validator = Validator::make( [], $rules );

        $this->assertFalse( $validator->passes() );
        $this->assertArrayHasKey( 'budget_ids', $validator->errors()->toArray() );
        $this->assertArrayHasKey( 'status', $validator->errors()->toArray() );
    }

    /** @test */
    public function it_validates_budget_ids_as_array()
    {
        $request = new BudgetBulkUpdateStatusFormRequest();
        $rules   = $request->rules();

        // Test with non-array value
        $validator = Validator::make( [
            'budget_ids' => 'not-an-array',
            'status'     => 'approved',
        ], $rules );

        $this->assertFalse( $validator->passes() );
        $this->assertArrayHasKey( 'budget_ids', $validator->errors()->toArray() );
    }

    /** @test */
    public function it_validates_budget_ids_array_has_minimum_one_item()
    {
        $request = new BudgetBulkUpdateStatusFormRequest();
        $rules   = $request->rules();

        // Test with empty array
        $validator = Validator::make( [
            'budget_ids' => [],
            'status'     => 'approved',
        ], $rules );

        $this->assertFalse( $validator->passes() );
        $this->assertArrayHasKey( 'budget_ids', $validator->errors()->toArray() );
    }

    /** @test */
    public function it_validates_budget_ids_array_has_maximum_100_items()
    {
        $request = new BudgetBulkUpdateStatusFormRequest();
        $rules   = $request->rules();

        // Test with more than 100 items
        $budgetIds = range( 1, 101 );

        $validator = Validator::make( [
            'budget_ids' => $budgetIds,
            'status'     => 'approved',
        ], $rules );

        $this->assertFalse( $validator->passes() );
        $this->assertArrayHasKey( 'budget_ids', $validator->errors()->toArray() );
    }

    /** @test */
    public function it_validates_each_budget_id_is_integer()
    {
        $request = new BudgetBulkUpdateStatusFormRequest();
        $rules   = $request->rules();

        // Test with non-integer values in array
        $validator = Validator::make( [
            'budget_ids' => [ 1, 'not-integer', 3 ],
            'status'     => 'approved',
        ], $rules );

        $this->assertFalse( $validator->passes() );
        $this->assertArrayHasKey( 'budget_ids.1', $validator->errors()->toArray() );
    }

    /** @test */
    public function it_validates_each_budget_id_exists_in_database()
    {
        $request = new BudgetBulkUpdateStatusFormRequest();
        $rules   = $request->rules();

        // Test with non-existent budget IDs
        $validator = Validator::make( [
            'budget_ids' => [ 999999, 999998 ],
            'status'     => 'approved',
        ], $rules );

        $this->assertFalse( $validator->passes() );
        $this->assertArrayHasKey( 'budget_ids.0', $validator->errors()->toArray() );
        $this->assertArrayHasKey( 'budget_ids.1', $validator->errors()->toArray() );
    }

    /** @test */
    public function it_validates_status_is_required_string()
    {
        $request = new BudgetBulkUpdateStatusFormRequest();
        $rules   = $request->rules();

        // Test with non-string status
        $validator = Validator::make( [
            'budget_ids' => [ 1 ],
            'status'     => 123,
        ], $rules );

        $this->assertFalse( $validator->passes() );
        $this->assertArrayHasKey( 'status', $validator->errors()->toArray() );
    }

    /** @test */
    public function it_validates_status_is_valid_enum_value()
    {
        $request = new BudgetBulkUpdateStatusFormRequest();
        $rules   = $request->rules();

        // Test with non-existent status
        $validator = Validator::make( [
            'budget_ids' => [ 1 ],
            'status'     => 'non_existent_status',
        ], $rules );

        $this->assertFalse( $validator->passes() );
        $this->assertArrayHasKey( 'status', $validator->errors()->toArray() );
    }

    /** @test */
    public function it_validates_comment_is_optional_string()
    {
        $request = new BudgetBulkUpdateStatusFormRequest();
        $rules   = $request->rules();

        // Test with valid comment
        $validator = Validator::make( [
            'budget_ids' => [ 1 ],
            'status'     => 'approved',
            'comment'    => 'This is a valid comment',
        ], $rules );

        // Should not fail on comment validation (other validations may fail)
        $errors = $validator->errors()->toArray();
        $this->assertArrayNotHasKey( 'comment', $errors );

        // Test with non-string comment
        $validator = Validator::make( [
            'budget_ids' => [ 1 ],
            'status'     => 'approved',
            'comment'    => 123,
        ], $rules );

        $this->assertFalse( $validator->passes() );
        $this->assertArrayHasKey( 'comment', $validator->errors()->toArray() );
    }

    /** @test */
    public function it_validates_comment_maximum_length()
    {
        $request = new BudgetBulkUpdateStatusFormRequest();
        $rules   = $request->rules();

        // Test with comment exceeding 1000 characters
        $longComment = str_repeat( 'a', 1001 );

        $validator = Validator::make( [
            'budget_ids' => [ 1 ],
            'status'     => 'approved',
            'comment'    => $longComment,
        ], $rules );

        $this->assertFalse( $validator->passes() );
        $this->assertArrayHasKey( 'comment', $validator->errors()->toArray() );
    }

    /** @test */
    public function it_validates_notify_customers_is_optional_boolean()
    {
        $request = new BudgetBulkUpdateStatusFormRequest();
        $rules   = $request->rules();

        // Test with valid boolean values
        $validator = Validator::make( [
            'budget_ids'       => [ 1 ],
            'status'           => 'approved',
            'notify_customers' => true,
        ], $rules );

        $errors = $validator->errors()->toArray();
        $this->assertArrayNotHasKey( 'notify_customers', $errors );

        $validator = Validator::make( [
            'budget_ids'       => [ 1 ],
            'status'           => 'approved',
            'notify_customers' => false,
        ], $rules );

        $errors = $validator->errors()->toArray();
        $this->assertArrayNotHasKey( 'notify_customers', $errors );

        // Test with non-boolean value
        $validator = Validator::make( [
            'budget_ids'       => [ 1 ],
            'status'           => 'approved',
            'notify_customers' => 'not-boolean',
        ], $rules );

        $this->assertFalse( $validator->passes() );
        $this->assertArrayHasKey( 'notify_customers', $validator->errors()->toArray() );
    }

    /** @test */
    public function it_passes_validation_with_valid_data()
    {
        // Create a real budget to test against
        $customer = \App\Models\Customer::factory()->create( [
            'tenant_id' => $this->tenant->id,
        ] );
        $budget   = \App\Models\Budget::factory()->create( [
            'tenant_id'   => $this->tenant->id,
            'customer_id' => $customer->id,
        ] );

        $request = new BudgetBulkUpdateStatusFormRequest();
        $rules   = $request->rules();

        $validData = [
            'budget_ids'       => [ $budget->id ],
            'status'           => 'approved',
            'comment'          => 'Valid comment',
            'notify_customers' => true,
        ];

        $validator = Validator::make( $validData, $rules );

        $this->assertTrue( $validator->passes() );
    }

    /** @test */
    public function it_provides_custom_error_messages()
    {
        $request  = new BudgetBulkUpdateStatusFormRequest();
        $messages = $request->messages();

        // Test that custom messages are defined
        $this->assertArrayHasKey( 'budget_ids.required', $messages );
        $this->assertArrayHasKey( 'budget_ids.array', $messages );
        $this->assertArrayHasKey( 'budget_ids.min', $messages );
        $this->assertArrayHasKey( 'budget_ids.max', $messages );
        $this->assertArrayHasKey( 'budget_ids.*.integer', $messages );
        $this->assertArrayHasKey( 'budget_ids.*.exists', $messages );
        $this->assertArrayHasKey( 'status.required', $messages );
        $this->assertArrayHasKey( 'status.string', $messages );
        $this->assertArrayHasKey( 'status.in', $messages );
        $this->assertArrayHasKey( 'comment.string', $messages );
        $this->assertArrayHasKey( 'comment.max', $messages );
        $this->assertArrayHasKey( 'notify_customers.boolean', $messages );

        // Test that messages are in Portuguese
        $this->assertStringContainsString( 'obrigatório', $messages[ 'budget_ids.required' ] );
        $this->assertStringContainsString( 'array', $messages[ 'budget_ids.array' ] );
        $this->assertStringContainsString( 'inválido', $messages[ 'status.in' ] );
    }

    /** @test */
    public function it_provides_custom_attribute_names()
    {
        $request    = new BudgetBulkUpdateStatusFormRequest();
        $attributes = $request->attributes();

        $this->assertArrayHasKey( 'budget_ids', $attributes );
        $this->assertArrayHasKey( 'status', $attributes );
        $this->assertArrayHasKey( 'comment', $attributes );
        $this->assertArrayHasKey( 'notify_customers', $attributes );

        // Test that attributes are in Portuguese
        $this->assertEquals( 'IDs dos orçamentos', $attributes[ 'budget_ids' ] );
        $this->assertEquals( 'Status', $attributes[ 'status' ] );
        $this->assertEquals( 'Comentário', $attributes[ 'comment' ] );
        $this->assertEquals( 'Notificar clientes', $attributes[ 'notify_customers' ] );
    }

    /** @test */
    public function it_validates_with_all_valid_budget_statuses()
    {
        $customer = \App\Models\Customer::factory()->create( [
            'tenant_id' => $this->tenant->id,
        ] );
        $budget   = \App\Models\Budget::factory()->create( [
            'tenant_id'   => $this->tenant->id,
            'customer_id' => $customer->id,
        ] );

        $request = new BudgetBulkUpdateStatusFormRequest();
        $rules   = $request->rules();

        $validStatuses = array_column( BudgetStatusEnum::cases(), 'value' );

        foreach ( $validStatuses as $status ) {
            $validator = Validator::make( [
                'budget_ids' => [ $budget->id ],
                'status'     => $status,
            ], $rules );

            $this->assertTrue( $validator->passes(), "Status '{$status}' should be valid" );
        }
    }

}
