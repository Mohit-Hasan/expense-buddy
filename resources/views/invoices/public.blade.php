<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $invoice->invoice_number }} — {{ $systemName }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
</head>
<body class="bg-slate-100 p-4 sm:p-8">
    <div class="mx-auto max-w-3xl">
        @include('invoices.partials.body')
        <div class="mt-6 flex flex-wrap justify-center gap-3 print:hidden">
            <a href="{{ route('invoices.public.pdf', $invoice->public_token) }}" class="btn-primary">Download PDF</a>
            <button type="button" onclick="window.print()" class="btn-secondary">Print</button>
        </div>
        @if ($invoice->expires_at)
            <p class="mt-4 text-center text-xs text-slate-500 print:hidden">This link expires {{ $invoice->expires_at->format('M j, Y') }}</p>
        @endif
    </div>
</body>
</html>
