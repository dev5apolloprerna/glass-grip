<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #222; font-size: 11px; }
        h1 { margin-bottom: 4px; font-size: 20px; }
        .meta { color: #555; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #bbb; padding: 7px; }
        th { background: #eee; text-align: left; }
        .number { text-align: right; }
    </style>
</head>
<body>
    <h1>Customer Ledger - {{ $selectedCustomer->name }}</h1>
    <div class="meta">
        Period: {{ $fromDate ? \Carbon\Carbon::parse($fromDate)->format('d M Y') : 'Beginning' }} to {{ $toDate ? \Carbon\Carbon::parse($toDate)->format('d M Y') : 'Today' }}<br>
        Opening Balance: Rs. {{ number_format($openingBalanceBeforeRange, 2) }}
    </div>
    <table>
        <thead><tr><th>Date</th><th>Description</th><th>Type</th><th class="number">Amount</th><th class="number">Balance After</th><th>Entered By</th></tr></thead>
        <tbody>
            @forelse($ledgers as $entry)
                <tr>
                    <td>{{ $entry->transaction_date->format('d M Y') }}</td>
                    <td>{{ $entry->description }}</td>
                    <td>{{ $entry->reference_type }}</td>
                    <td class="number">{{ $entry->amount >= 0 ? '+' : '-' }}Rs. {{ number_format(abs($entry->amount), 2) }}</td>
                    <td class="number">Rs. {{ number_format($entry->balance_after, 2) }}</td>
                    <td>{{ $entry->enteredBy->name ?? '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="6" style="text-align:center">No transactions in this range.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
