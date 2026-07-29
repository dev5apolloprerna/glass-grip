<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\DeliveryChallan;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuotationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_sending_approving_and_generating_an_invoice_are_separate_steps(): void
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
            ->post(route('quotations.approve', $quotation))
            ->assertRedirect(route('quotations.show', $quotation));

        $quotation->refresh();
        $this->assertSame('approved', $quotation->status);
        $this->assertNull($quotation->invoice);

        $this->actingAs($user)
            ->post(route('quotations.generate-invoice', $quotation), ['invoice_number' => ' INV-MANUAL-42 '])
            ->assertRedirect(route('quotations.show', $quotation));

        $quotation->refresh();
        $this->assertSame('INV-MANUAL-42', $quotation->invoice->invoice_number);
        $this->assertNull($quotation->invoice->deliveryChallan);
    }

    public function test_a_quotation_cannot_be_approved_before_it_is_sent(): void
    {
        $user = User::factory()->create();
        $quotation = $this->createQuotation($user);

        $this->actingAs($user)
            ->post(route('quotations.approve', $quotation))
            ->assertSessionHas('error', 'Send the quotation before approving it.');

        $this->assertDatabaseMissing('invoices', ['invoice_number' => 'INV-100']);
    }
public function test_an_unsent_quotation_cannot_generate_an_invoice(): void
    {
        $user = User::factory()->create();
        $quotation = $this->createQuotation($user);

        $this->actingAs($user)
            ->post(route('quotations.generate-invoice', $quotation), ['invoice_number' => 'INV-UNSENT'])
            ->assertSessionHas('error', 'Send the quotation before generating an invoice.');

        $this->assertNull($quotation->fresh()->invoice);
    }

    public function test_sent_quotation_shows_separate_approve_and_generate_invoice_buttons(): void
    {
        $user = User::factory()->create();
        $quotation = $this->createQuotation($user);
        $this->actingAs($user)->post(route('quotations.mark-sent', $quotation));

        $this->actingAs($user)
            ->get(route('quotations.show', $quotation))
            ->assertOk()
            ->assertSee('Approve Quotation')
            ->assertSee('data-modal-open="generateInvoiceModal"', false)
            ->assertSee('name="invoice_number"', false);
    }

    public function test_sent_quotation_can_generate_an_invoice_without_approval(): void
    {
        $user = User::factory()->create();
        $quotation = $this->createQuotation($user);
        $this->actingAs($user)->post(route('quotations.mark-sent', $quotation));

        $this->actingAs($user)
            ->post(route('quotations.generate-invoice', $quotation), ['invoice_number' => 'INV-WITHOUT-APPROVAL'])
            ->assertRedirect(route('quotations.show', $quotation));

        $quotation->refresh();
        $this->assertTrue($quotation->isSent());
        $this->assertSame('INV-WITHOUT-APPROVAL', $quotation->invoice->invoice_number);


        $this->actingAs($user)
            ->get(route('quotations.show', $quotation))
            ->assertOk()
            ->assertSee('Approve Quotation')
            ->assertSee('Generate Invoice')
            ->assertSee('Approve the quotation first to generate its invoice')
            ->assertSee('View Invoice')
            ->assertSee('Generate Delivery Challan');
    }

    public function test_approved_quotation_shows_separate_generate_invoice_modal_and_download_after_generation(): void
    {
        $user = User::factory()->create();
        $quotation = $this->createQuotation($user);
        $this->actingAs($user)->post(route('quotations.mark-sent', $quotation));

        $this->actingAs($user)
            ->post(route('quotations.approve', $quotation))
            ->assertRedirect(route('quotations.show', $quotation))
            ->assertSessionHas('success', 'Quotation approved successfully. You can now generate the invoice.');

        $quotation->refresh();
        $this->assertSame('approved', $quotation->status);
        $this->assertNull($quotation->invoice);

        $this->actingAs($user)
            ->get(route('quotations.show', $quotation))
            ->assertOk()
            ->assertSee('class="quotation-actions"', false)
            ->assertSee('class="quotation-actions-group quotation-actions-workflow"', false)
            ->assertSee('class="quotation-actions-group quotation-actions-manage"', false)
            ->assertSee('data-modal-open="generateInvoiceModal"', false)
            ->assertSee('name="invoice_number"', false)
            ->assertDontSee('Collect Payment');

        $this->actingAs($user)
            ->post(route('quotations.generate-invoice', $quotation), ['invoice_number' => 'INV-APPROVED-1'])
            ->assertRedirect(route('quotations.show', $quotation));

        $quotation->refresh();
        $this->assertNotNull($quotation->invoice);

        $this->actingAs($user)
            ->get(route('quotations.show', $quotation))
            ->assertOk()
            ->assertSee('Download PDF')
            ->assertSee(route('invoices.download', $quotation->invoice), false)
            ->assertSee('Generate Delivery Challan')
            ->assertSee(route('delivery-challans.store', $quotation->invoice), false);
    }

    public function test_an_approved_quotation_shows_the_delivery_challan_action(): void
    {
        $user = User::factory()->create();
        $quotation = $this->createQuotation($user);

        $this->actingAs($user)->post(route('quotations.mark-sent', $quotation));
        $this->actingAs($user)->post(route('quotations.approve', $quotation));
        $this->actingAs($user)->post(route('quotations.generate-invoice', $quotation), [
            'invoice_number' => 'INV-DELIVERY-1',
        ]);

        $quotation->refresh();

        $this->actingAs($user)
            ->get(route('quotations.show', $quotation))
            ->assertOk()
            ->assertSee('Generate Delivery Challan')
            ->assertSee(route('delivery-challans.store', $quotation->invoice), false);

        $challan = DeliveryChallan::create([
            'challan_number' => 'DC-TEST-1',
            'invoice_id' => $quotation->invoice->id,
            'challan_date' => now()->toDateString(),
        ]);

        $this->actingAs($user)
            ->get(route('quotations.show', $quotation))
            ->assertOk()
            ->assertSee('Delivery Challan')
            ->assertSee(route('delivery-challans.show', $challan), false)
            ->assertDontSee('Generate Delivery Challan');
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
