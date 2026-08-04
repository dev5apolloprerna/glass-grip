<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentCollectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_can_collect_customer_wise_payment_and_receipt_is_created(): void
    {
        $employee = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $customer = Customer::create(['name' => 'Test Customer', 'opening_balance' => 0, 'created_by' => $employee->id]);
        $quotation = Quotation::create(['quotation_number' => 'Q-1', 'customer_id' => $customer->id, 'user_id' => $employee->id, 'quotation_date' => now(), 'status' => 'approved', 'gst_applicable' => false, 'sub_total' => 500, 'gst_amount' => 0, 'total_amount' => 500]);
        Invoice::create(['invoice_number' => 'I-1', 'quotation_id' => $quotation->id, 'customer_id' => $customer->id, 'invoice_date' => now(), 'sub_total' => 500, 'gst_amount' => 0, 'total_amount' => 500]);
        $customer->ledgers()->create(['transaction_date' => now(), 'amount' => 500, 'description' => 'Invoice I-1', 'reference_type' => 'invoice', 'entered_by' => $employee->id, 'balance_after' => 500]);

        $response = $this->actingAs($employee)->post(route('payment-collections.store'), [
            'employee_id' => $employee->id, 'customer_id' => $customer->id, 'payment_date' => now()->toDateString(),
            'amount' => 200, 'payment_method' => 'cash',
        ]);

        $response->assertRedirect(route('payment-collections.index', ['employee_id' => $employee->id]));
        $this->assertDatabaseHas('payments', ['customer_id' => $customer->id, 'invoice_id' => null, 'employee_id' => $employee->id, 'amount' => 200, 'receipt_number' => 'PR-000001']);
        $this->assertSame(300.0, $customer->fresh()->currentBalance());
    }

    public function test_invalid_customer_collection_is_rejected(): void
    {
        $employee = User::factory()->create(['role' => 'user']);
        $other = User::factory()->create(['role' => 'user']);

        $this->actingAs($employee)->post(route('payment-collections.store'), [
            'employee_id' => $other->id, 'customer_id' => 999, 'payment_date' => now()->toDateString(), 'amount' => 1, 'payment_method' => 'cash',
        ])->assertSessionHasErrors('customer_id');
    }
}
