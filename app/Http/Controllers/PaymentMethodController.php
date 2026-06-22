<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StorePaymentMethodRequest;
use App\Http\Requests\UpdatePaymentMethodRequest;
use App\Models\PaymentMethod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentMethodController extends Controller
{
    public function index(Request $request): View
    {
        $query = PaymentMethod::query()
            ->withCount('transactions')
            ->orderBy('status')
            ->orderBy('name');

        if ($request->filled('status') && in_array($request->input('status'), ['active', 'inactive'], true)) {
            $query->where('status', $request->input('status'));
        }

        return view('payment-methods.index', [
            'paymentMethods' => $query->get(),
            'filters' => $request->only(['status']),
            'stats' => [
                'total' => PaymentMethod::query()->count(),
                'active' => PaymentMethod::query()->where('status', 'active')->count(),
                'archived' => PaymentMethod::query()->where('status', 'inactive')->count(),
            ],
        ]);
    }

    public function store(StorePaymentMethodRequest $request): RedirectResponse
    {
        PaymentMethod::query()->create([
            ...$request->validated(),
            'status' => 'active',
        ]);

        return redirect()
            ->route('payment-methods.index')
            ->with('success', 'Payment method created successfully.');
    }

    public function update(UpdatePaymentMethodRequest $request, int $id): RedirectResponse
    {
        $method = PaymentMethod::query()->find($id);

        if ($method === null) {
            return back()->withErrors(['form' => 'Payment method not found.']);
        }

        $method->update($request->validated());

        $message = $method->status === 'inactive'
            ? 'Payment method archived successfully.'
            : 'Payment method updated successfully.';

        return redirect()
            ->route('payment-methods.index')
            ->with('success', $message);
    }

    public function archive(int $id): RedirectResponse
    {
        $method = PaymentMethod::query()->find($id);

        if ($method === null) {
            return back()->withErrors(['form' => 'Payment method not found.']);
        }

        $method->update(['status' => 'inactive']);

        return redirect()
            ->route('payment-methods.index')
            ->with('success', 'Payment method archived. Existing transactions keep this method.');
    }

    public function restore(int $id): RedirectResponse
    {
        $method = PaymentMethod::query()->find($id);

        if ($method === null) {
            return back()->withErrors(['form' => 'Payment method not found.']);
        }

        $method->update(['status' => 'active']);

        return redirect()
            ->route('payment-methods.index')
            ->with('success', 'Payment method restored to active.');
    }
}
