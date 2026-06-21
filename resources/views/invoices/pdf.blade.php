<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #0f172a; font-size: 12px; margin: 0; padding: 32px; }
        .header { border-bottom: 2px solid #0d9488; padding-bottom: 16px; margin-bottom: 24px; }
        .title { font-size: 24px; font-weight: bold; color: #0d9488; }
        table { width: 100%; border-collapse: collapse; margin-top: 24px; }
        th, td { padding: 10px; border-bottom: 1px solid #e2e8f0; text-align: left; }
        th { background: #f8fafc; font-size: 11px; text-transform: uppercase; }
        .amount { font-size: 20px; font-weight: bold; text-align: right; margin-top: 16px; }
        .muted { color: #64748b; }
    </style>
</head>
<body>
    @php use App\Support\MoneyFormatter; $txn = $transaction; @endphp
    <div class="header">
        <div class="title">{{ $systemName }}</div>
        <div class="muted">Invoice {{ $invoice->invoice_number }}</div>
    </div>
    <table>
        <tr><th>Date</th><td>{{ $txn->transaction_date->format('F j, Y') }}</td></tr>
        <tr><th>Type</th><td>{{ ucfirst($txn->type) }}</td></tr>
        @if ($txn->contact)
            <tr><th>{{ ucfirst($txn->contact->type) }}</th><td>{{ $txn->contact->name }}</td></tr>
        @endif
        <tr><th>Account</th><td>{{ $txn->account?->account_title }}</td></tr>
        <tr><th>Reference</th><td>{{ $txn->reference ?? '—' }}</td></tr>
        <tr><th>Description</th><td>{{ $txn->description ?? '—' }}</td></tr>
    </table>
    <div class="amount">{{ MoneyFormatter::format((string) $txn->amount, $txn->currency) }}</div>
    @if ($invoice->expires_at)
        <p class="muted">Public link expires: {{ $invoice->expires_at->format('M j, Y g:i A') }}</p>
    @endif
</body>
</html>
