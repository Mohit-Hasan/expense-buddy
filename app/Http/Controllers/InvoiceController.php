<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Services\InvoiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

class InvoiceController extends Controller
{
    public function __construct(
        private readonly InvoiceService $invoiceService,
    ) {
    }

    public function show(int $transactionId): View
    {
        $transaction = Transaction::query()
            ->with(['latestInvoice', 'account', 'currency', 'contact', 'category', 'paymentMethod'])
            ->findOrFail($transactionId);

        if ($transaction->type === 'transfer') {
            abort(404);
        }

        $invoice = $transaction->latestInvoice
            ?? $this->invoiceService->createOrRefresh($transactionId);

        return view('invoices.show', $this->invoiceService->viewData($invoice));
    }

    public function download(int $transactionId): Response
    {
        $invoice = $this->resolveInvoice($transactionId);

        return $this->invoiceService->pdfResponse($invoice);
    }

    public function share(Request $request, int $transactionId): RedirectResponse
    {
        $validated = $request->validate([
            'expires_in_days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ]);

        $days = (int) ($validated['expires_in_days'] ?? 30);

        $invoice = $this->invoiceService->createOrRefresh(
            $transactionId,
            makePublic: true,
            expiresInDays: $days
        );

        return back()->with('success', 'Public invoice link created. Expires in '.$days.' days.')
            ->with('invoice_public_url', route('invoices.public', $invoice->public_token));
    }

    public function revoke(int $transactionId): RedirectResponse
    {
        try {
            $this->invoiceService->revokePublicLink($transactionId);
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['form' => $exception->getMessage()]);
        }

        return back()->with('success', 'Public invoice link revoked.');
    }

    public function publicShow(string $token): View
    {
        $invoice = $this->invoiceService->findByToken($token);

        if ($invoice === null || ! $invoice->isAccessible()) {
            abort(404, 'This invoice link is invalid or has expired.');
        }

        return view('invoices.public', $this->invoiceService->viewData($invoice));
    }

    public function publicDownload(string $token): Response
    {
        $invoice = $this->invoiceService->findByToken($token);

        if ($invoice === null || ! $invoice->isAccessible()) {
            abort(404, 'This invoice link is invalid or has expired.');
        }

        return $this->invoiceService->pdfResponse($invoice);
    }

    private function resolveInvoice(int $transactionId): \App\Models\TransactionInvoice
    {
        $transaction = Transaction::query()->with('latestInvoice')->findOrFail($transactionId);

        if ($transaction->type === 'transfer') {
            throw new InvalidArgumentException('Transfer transactions cannot be invoiced.');
        }

        return $transaction->latestInvoice
            ?? $this->invoiceService->createOrRefresh($transactionId);
    }
}
