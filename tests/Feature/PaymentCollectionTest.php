<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Quotation;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentCollectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_collect_company_wise_payment_and_receipt_is_created(): void
    {
        $employee = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $customer = Customer::create(['name' => 'Test Customer', 'opening_balance' => 0, 'created_by' => $employee->id]);
        $quotation = Quotation::create(['quotation_number' => 'Q-1', 'customer_id' => $customer->id, 'user_id' => $employee->id, 'quotation_date' => now(), 'status' => 'approved', 'gst_applicable' => false, 'sub_total' => 500, 'gst_amount' => 0, 'total_amount' => 500]);
        Invoice::create(['invoice_number' => 'I-1', 'quotation_id' => $quotation->id, 'customer_id' => $customer->id, 'invoice_date' => now(), 'sub_total' => 500, 'gst_amount' => 0, 'total_amount' => 500]);
        $customer->ledgers()->create(['transaction_date' => now(), 'amount' => 500, 'description' => 'Invoice I-1', 'reference_type' => 'invoice', 'entered_by' => $employee->id, 'balance_after' => 500]);

        $response = $this->actingAs($employee)->post(route('payment-collections.store'), [
            'customer_id' => $customer->id, 'payment_date' => now()->toDateString(),
            'amount' => 200, 'payment_method' => 'cash',
        ]);

        $response->assertRedirect(route('payment-collections.index'));
        $this->assertDatabaseHas('payments', ['customer_id' => $customer->id, 'invoice_id' => null, 'employee_id' => null, 'amount' => 200, 'receipt_number' => 'PR-000001']);
        $this->assertSame(300.0, $customer->fresh()->currentBalance());
    }

    public function test_invalid_customer_collection_is_rejected(): void
    {
        $employee = User::factory()->create(['role' => 'user']);

        $this->actingAs($employee)->post(route('payment-collections.store'), [
            'customer_id' => 999, 'payment_date' => now()->toDateString(), 'amount' => 1, 'payment_method' => 'cash',
        ])->assertSessionHasErrors('customer_id');
    }
    public function test_collection_page_can_search_by_company_name(): void
    {
        $employee = User::factory()->create(['role' => 'user']);
        $matchingCustomer = Customer::create(['name' => 'Acme Glass Industries', 'created_by' => $employee->id]);
        $otherCustomer = Customer::create(['name' => 'Bright Windows Limited', 'created_by' => $employee->id]);

        foreach ([$matchingCustomer, $otherCustomer] as $index => $customer) {
            $quotation = Quotation::create([
                'quotation_number' => 'Q-SEARCH-'.$index,
                'customer_id' => $customer->id,
                'user_id' => $employee->id,
                'quotation_date' => now(),
                'status' => 'approved',
                'gst_applicable' => false,
                'sub_total' => 100,
                'gst_amount' => 0,
                'total_amount' => 100,
            ]);
            Invoice::create([
                'invoice_number' => 'I-SEARCH-'.$index,
                'quotation_id' => $quotation->id,
                'customer_id' => $customer->id,
                'invoice_date' => now(),
                'sub_total' => 100,
                'gst_amount' => 0,
                'total_amount' => 100,
            ]);
        }

        $this->actingAs($employee)->get(route('payment-collections.index', ['company' => 'acme glass']))
            ->assertOk()
            ->assertSee('Acme Glass Industries')
            ->assertDontSee('Bright Windows Limited')
            ->assertSee('value="acme glass"', false);
    }

    public function test_collection_page_shows_a_message_when_company_search_has_no_matches(): void
    {
        $employee = User::factory()->create(['role' => 'user']);

        $this->actingAs($employee)->get(route('payment-collections.index', ['company' => 'Unknown Company']))
            ->assertOk()
            ->assertSee('No companies match your search.');
    }

    public function test_collection_page_combines_companies_and_payments_across_users(): void
    {
        $viewer = User::factory()->create(['role' => 'user']);
        $otherEmployee = User::factory()->create(['role' => 'user']);
        $customer = Customer::create(['name' => 'Other Employee Company', 'created_by' => $otherEmployee->id]);
        $quotation = Quotation::create(['quotation_number' => 'Q-2', 'customer_id' => $customer->id, 'user_id' => $otherEmployee->id, 'quotation_date' => now(), 'status' => 'approved', 'gst_applicable' => false, 'sub_total' => 750, 'gst_amount' => 0, 'total_amount' => 750]);
        $invoice = Invoice::create(['invoice_number' => 'I-2', 'quotation_id' => $quotation->id, 'customer_id' => $customer->id, 'invoice_date' => now(), 'sub_total' => 750, 'gst_amount' => 0, 'total_amount' => 750]);
        $invoice->payments()->create(['customer_id' => $customer->id, 'payment_date' => now(), 'amount' => 250, 'payment_method' => 'cash', 'entered_by' => $otherEmployee->id]);

        $this->actingAs($viewer)->get(route('payment-collections.index'))
            ->assertOk()
            ->assertSee('Company-wise Payment Collection')
            ->assertSee('Other Employee Company')
            ->assertSee('750.00')
            ->assertSee('250.00')
            ->assertSee('500.00');
    }
    public function test_latest_company_receipt_download_is_shown_with_collection_action(): void
    {
        $employee = User::factory()->create(['role' => 'user']);
        $customer = Customer::create(['name' => 'Receipt Company', 'created_by' => $employee->id]);
        $quotation = Quotation::create(['quotation_number' => 'Q-3', 'customer_id' => $customer->id, 'user_id' => $employee->id, 'quotation_date' => now(), 'status' => 'approved', 'gst_applicable' => false, 'sub_total' => 500, 'gst_amount' => 0, 'total_amount' => 500]);
        Invoice::create(['invoice_number' => 'I-3', 'quotation_id' => $quotation->id, 'customer_id' => $customer->id, 'invoice_date' => now(), 'sub_total' => 500, 'gst_amount' => 0, 'total_amount' => 500]);
        $payment = Payment::create(['customer_id' => $customer->id, 'payment_date' => now(), 'amount' => 100, 'payment_method' => 'cash', 'receipt_number' => 'PR-000001', 'entered_by' => $employee->id]);

        $this->actingAs($employee)->get(route('payment-collections.index'))
            ->assertOk()
            ->assertSee('Collect Payment')
            ->assertSee(route('payment-collections.receipt', $payment), false)
            ->assertSee('Download Receipt')
            ->assertDontSee('Recent Company Payments');
    }

    public function test_receipt_includes_amount_in_words_and_current_due_amount(): void
    {
        $employee = User::factory()->create(['role' => 'user']);
        $customer = Customer::create(['name' => 'Receipt Details Company', 'created_by' => $employee->id]);
        $customer->ledgers()->create(['transaction_date' => now(), 'amount' => 376.55, 'description' => 'Outstanding balance', 'reference_type' => 'invoice', 'entered_by' => $employee->id, 'balance_after' => 376.55]);
        $payment = Payment::create(['customer_id' => $customer->id, 'payment_date' => now(), 'amount' => 123.45, 'payment_method' => 'cash', 'receipt_number' => 'PR-000002', 'entered_by' => $employee->id]);

        Pdf::shouldReceive('loadView')
            ->once()
            ->with('payments.receipt', \Mockery::on(function (array $data): bool {
                return $data['amountInWords'] === 'Indian Rupees One hundred twenty-three and Forty-five Paise Only'
                    && $data['dueAmount'] === 376.55;
            }))
            ->andReturnSelf();
        Pdf::shouldReceive('setPaper')->once()->with('a5')->andReturnSelf();
        Pdf::shouldReceive('download')->once()->with('PR-000002.pdf')->andReturn(response('pdf'));

        $this->actingAs($employee)->get(route('payment-collections.receipt', $payment))->assertOk();
    }

}
