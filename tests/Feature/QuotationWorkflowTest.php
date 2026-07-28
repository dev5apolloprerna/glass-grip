<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuotationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_sending_and_approving_are_separate_steps_with_a_manual_invoice_number(): void
    {
        $user = User::factory()->create();
        $quotation = $this->createQuotation($user);

        $this->actingAs($user)
            ->post(route('quotations.mark-sent', $quotation))
            ->assertRedirect(route('quotations.show', $quotation));

        $quotation->refresh();
        $this->assertTrue($quotation->isSent());
        $this->assertSame('draft', $quotation->status);
        $this->assertNull($quotation->invoice);

        $this->actingAs($user)
            ->post(route('quotations.approve', $quotation), ['invoice_number' => ' INV-MANUAL-42 '])
            ->assertRedirect(route('quotations.show', $quotation));

        $quotation->refresh();
        $this->assertSame('approved', $quotation->status);
        $this->assertSame('INV-MANUAL-42', $quotation->invoice->invoice_number);
    }

    public function test_a_quotation_cannot_be_approved_before_it_is_sent(): void
    {
        $user = User::factory()->create();
        $quotation = $this->createQuotation($user);

        $this->actingAs($user)
            ->post(route('quotations.approve', $quotation), ['invoice_number' => 'INV-100'])
            ->assertSessionHas('error', 'Send the quotation before approving it.');

        $this->assertDatabaseMissing('invoices', ['invoice_number' => 'INV-100']);
    }

    private function createQuotation(User $user): Quotation
    {
        $customer = Customer::create(['name' => 'Test Customer', 'created_by' => $user->id]);
        $product = Product::create(['name' => 'Test Product', 'unit' => 'Mtr', 'status' => 'active']);
        $quotation = Quotation::create([
            'quotation_number' => 'QUO-TEST-' . uniqid(),
            'customer_id' => $customer->id,
            'user_id' => $user->id,
            'quotation_date' => now()->toDateString(),
            'status' => 'draft',
            'document_status' => 'quotation_ready',
            'sub_total' => 100,
            'total_amount' => 100,
        ]);
        QuotationItem::create([
            'quotation_id' => $quotation->id,
            'product_id' => $product->id,
            'size_mtr' => 10,
            'no_of_rolls' => 1,
            'total_mtr' => 10,
            'price_per_mtr' => 10,
            'amount' => 100,
        ]);

        return $quotation;
    }
}
