<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerLedger;
use App\Models\Invoice;
use App\Models\NumberSetting;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\QuotationItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class QuotationController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $search = $request->get('search');
        $status = $request->get('status');

        $quotations = Quotation::with(['customer', 'user'])
            ->when(! $user->isSuperAdmin(), fn ($q) => $q->where('user_id', $user->id))
            ->when($search, function ($q) use ($search) {
                $q->where(function ($qq) use ($search) {
                    $qq->where('quotation_number', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($status, fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('quotations.index', compact('quotations', 'search', 'status'));
    }

    public function create()
    {
        $customers = Customer::orderBy('name')->get();
        $products = Product::where('status', 'active')->orderBy('name')->get();

        return view('quotations.create', compact('customers', 'products'));
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        $quotation = DB::transaction(function () use ($data) {
            $quotation = Quotation::create([
                'quotation_number' => NumberSetting::generateNext('quotation'),
                'customer_id' => $data['customer_id'],
                'user_id' => Auth::id(),
                'quotation_date' => $data['quotation_date'],
                'status' => 'draft',
                'gst_applicable' => $data['gst_applicable'] ?? false,
            ]);

            $this->syncItems($quotation, $data['items']);
            $quotation->recalculateTotals();

            return $quotation;
        });

        return redirect()->route('quotations.show', $quotation)->with('success', 'Quotation created successfully.');
    }

    public function show(Quotation $quotation)
    {
        $this->authorizeAccess($quotation);
        $quotation->load(['items.product', 'customer', 'user', 'approvedBy', 'invoice']);

        return view('quotations.show', compact('quotation'));
    }

    public function edit(Quotation $quotation)
    {
        $this->authorizeAccess($quotation);

        if (! $quotation->isEditable()) {
            return redirect()->route('quotations.show', $quotation)->with('error', 'Approved quotations cannot be edited.');
        }

        $quotation->load('items.product');
        $customers = Customer::orderBy('name')->get();
        $products = Product::where('status', 'active')->orderBy('name')->get();

        return view('quotations.edit', compact('quotation', 'customers', 'products'));
    }

    public function update(Request $request, Quotation $quotation)
    {
        $this->authorizeAccess($quotation);

        if (! $quotation->isEditable()) {
            return redirect()->route('quotations.show', $quotation)->with('error', 'Approved quotations cannot be edited.');
        }

        $data = $this->validateData($request);

        DB::transaction(function () use ($quotation, $data) {
            $quotation->update([
                'customer_id' => $data['customer_id'],
                'quotation_date' => $data['quotation_date'],
                'gst_applicable' => $data['gst_applicable'] ?? false,
            ]);

            $quotation->items()->delete();
            $this->syncItems($quotation, $data['items']);
            $quotation->recalculateTotals();
        });

        return redirect()->route('quotations.show', $quotation)->with('success', 'Quotation updated successfully.');
    }

    public function destroy(Quotation $quotation)
    {
        $this->authorizeAccess($quotation);

         DB::transaction(function () use ($quotation) {
            if ($quotation->invoice) {
                CustomerLedger::where('reference_type', 'invoice')
                    ->where('reference_id', $quotation->invoice->id)
                    ->delete();
            }

            $quotation->items()->delete();
            $quotation->delete();
        });

        return redirect()->route('quotations.index')->with('success', 'Quotation deleted successfully.');
        
    }

    /**
     * Approve the quotation: locks editing and generates an invoice.
     */
    public function approve(Quotation $quotation)
    {
        $this->authorizeAccess($quotation);

        if (! $quotation->isEditable()) {
            return back()->with('error', 'Quotation is already approved.');
        }

        if ($quotation->items()->count() === 0) {
            return back()->with('error', 'Cannot approve a quotation with no items.');
        }

        DB::transaction(function () use ($quotation) {
            $quotation->update([
                'status' => 'approved',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ]);

            $invoice = Invoice::create([
                'invoice_number' => NumberSetting::generateNext('invoice'),
                'quotation_id' => $quotation->id,
                'customer_id' => $quotation->customer_id,
                'invoice_date' => now()->toDateString(),
                'sub_total' => $quotation->sub_total,
                'gst_amount' => $quotation->gst_amount,
                'total_amount' => $quotation->total_amount,
            ]);

            $customer = $quotation->customer;
            $newBalance = $customer->currentBalance() + (float) $quotation->total_amount;

            CustomerLedger::create([
                'customer_id' => $customer->id,
                'transaction_date' => $invoice->invoice_date,
                'amount' => $quotation->total_amount,
                'description' => 'Invoice ' . $invoice->invoice_number,
                'reference_type' => 'invoice',
                'reference_id' => $invoice->id,
                'entered_by' => Auth::id(),
                'balance_after' => $newBalance,
            ]);
        });

        return redirect()->route('quotations.show', $quotation)->with('success', 'Quotation approved and invoice generated.');
    }
     public function reject(Quotation $quotation)
    {
        $this->authorizeAccess($quotation);

        if (! $quotation->isEditable()) {
            return back()->with('error', 'Only draft quotations can be rejected.');
        }

        $quotation->update(['status' => 'rejected']);

        return redirect()->route('quotations.show', $quotation)->with('success', 'Quotation rejected successfully.');
    }

    /**
     * AJAX: return the last price charged to this customer for this product.
     */
    public function lastPrice(Request $request)
    {
        $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'product_id' => ['required', 'exists:products,id'],
        ]);

        $item = QuotationItem::query()
            ->where('product_id', $request->product_id)
            ->whereHas('quotation', function ($q) use ($request) {
                $q->where('customer_id', $request->customer_id)
                    ->where('status', 'approved');
            })
            ->join('quotations', 'quotations.id', '=', 'quotation_items.quotation_id')
            ->orderByDesc('quotations.approved_at')
            ->select('quotation_items.*')
            ->first();

        return response()->json([
            'found' => (bool) $item,
            'price_per_mtr' => $item ? (float) $item->price_per_mtr : null,
        ]);
    }

    private function authorizeAccess(Quotation $quotation): void
    {
        $user = Auth::user();
        if (! $user->isSuperAdmin() && $quotation->user_id !== $user->id) {
            abort(403, 'You do not have permission to access this quotation.');
        }
    }

    private function syncItems(Quotation $quotation, array $items): void
    {
        foreach ($items as $item) {
            $sizeMtr = (float) $item['size_mtr'];
            $noOfRolls = (int) $item['no_of_rolls'];
            $pricePerMtr = (float) $item['price_per_mtr'];
            $totalMtr = $sizeMtr * $noOfRolls;
            $amount = $totalMtr * $pricePerMtr;

            QuotationItem::create([
                'quotation_id' => $quotation->id,
                'product_id' => $item['product_id'],
                'size_mtr' => $sizeMtr,
                'no_of_rolls' => $noOfRolls,
                'total_mtr' => $totalMtr,
                'price_per_mtr' => $pricePerMtr,
                'amount' => $amount,
            ]);
        }
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'quotation_date' => ['required', 'date'],
            'gst_applicable' => ['nullable', 'boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.size_mtr' => ['required', 'numeric', 'min:0.01'],
            'items.*.no_of_rolls' => ['required', 'integer', 'min:1'],
            'items.*.price_per_mtr' => ['required', 'numeric', 'min:0'],
        ]);
    }
}
