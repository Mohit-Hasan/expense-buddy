<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SystemSetting;
use App\Models\Transaction;
use App\Models\TransactionInvoice;
use App\Support\Brand;
use App\Support\MoneyFormatter;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

class InvoiceService
{
    public function revokePublicLink(int $transactionId): TransactionInvoice
    {
        $transaction = Transaction::query()->find($transactionId);

        if ($transaction === null) {
            throw new InvalidArgumentException('Transaction not found.');
        }

        $invoice = $transaction->invoices()->latest()->first();

        if ($invoice === null) {
            throw new InvalidArgumentException('Invoice not found.');
        }

        $invoice->update(['is_public' => false]);

        return $invoice->fresh(['transaction.account', 'transaction.currency', 'transaction.contact']);
    }

    public function createOrRefresh(int $transactionId, bool $makePublic = false, ?int $expiresInDays = 30): TransactionInvoice
    {
        $transaction = Transaction::query()
            ->with(['account', 'currency', 'contact', 'category', 'paymentMethod'])
            ->find($transactionId);

        if ($transaction === null) {
            throw new InvalidArgumentException('Transaction not found.');
        }

        if ($transaction->type === 'transfer') {
            throw new InvalidArgumentException('Transfer transactions cannot be invoiced.');
        }

        $invoice = $transaction->invoices()->latest()->first();

        if ($invoice === null) {
            $invoice = new TransactionInvoice([
                'transaction_id' => $transaction->id,
                'invoice_number' => $this->generateInvoiceNumber(),
                'public_token' => Str::random(48),
            ]);
        }

        $invoice->is_public = $makePublic;
        $invoice->expires_at = $makePublic && $expiresInDays !== null
            ? now()->addDays($expiresInDays)
            : null;
        $invoice->save();

        return $invoice->fresh(['transaction.account', 'transaction.currency', 'transaction.contact']);
    }

    public function findByToken(string $token): ?TransactionInvoice
    {
        return TransactionInvoice::query()
            ->with(['transaction.account', 'transaction.currency', 'transaction.contact', 'transaction.category', 'transaction.paymentMethod'])
            ->where('public_token', $token)
            ->first();
    }

    public function pdfResponse(TransactionInvoice $invoice, bool $download = true): Response
    {
        $html = view('invoices.pdf', $this->viewData($invoice))->render();
        $filename = $invoice->invoice_number.'.pdf';

        $pdf = Pdf::loadHTML($html)->setPaper('a4');

        return $download
            ? $pdf->download($filename)
            : $pdf->stream($filename);
    }

    /**
     * @return array<string, mixed>
     */
    public function viewData(TransactionInvoice $invoice): array
    {
        $settings = Schema::hasTable('system_settings')
            ? SystemSetting::query()->with('defaultCurrency')->first()
            : null;

        return [
            'invoice' => $invoice,
            'transaction' => $invoice->transaction,
            'systemName' => Brand::appName($settings),
            'baseCurrency' => MoneyFormatter::baseCurrency(),
        ];
    }

    private function generateInvoiceNumber(): string
    {
        $prefix = 'INV-'.now()->format('Ym').'-';

        $latest = TransactionInvoice::query()
            ->where('invoice_number', 'like', $prefix.'%')
            ->orderByDesc('invoice_number')
            ->value('invoice_number');

        $sequence = $latest !== null
            ? ((int) substr((string) $latest, -4)) + 1
            : 1;

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
