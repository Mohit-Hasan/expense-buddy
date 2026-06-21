<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreAccountRequest;
use App\Http\Requests\UpdateAccountRequest;
use App\Models\Currency;
use App\Repositories\Contracts\AccountRepositoryInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function __construct(
        private readonly AccountRepositoryInterface $accountRepository,
    ) {
    }

    public function index(): View
    {
        return view('accounts.index', [
            'accounts' => $this->accountRepository->all(),
            'currencies' => Currency::query()->orderBy('name')->get(),
        ]);
    }

    public function store(StoreAccountRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $initialBalance = (string) $data['initial_balance'];

        $this->accountRepository->create([
            'account_title' => $data['account_title'],
            'account_number' => $data['account_number'] ?? null,
            'currency_id' => $data['currency_id'],
            'initial_balance' => $initialBalance,
            'current_balance' => $initialBalance,
            'note' => $data['note'] ?? null,
        ]);

        return redirect()
            ->route('accounts.index')
            ->with('success', 'Account created successfully.');
    }

    public function update(UpdateAccountRequest $request, int $id): RedirectResponse
    {
        $updated = $this->accountRepository->update($id, $request->validated());

        if (! $updated) {
            return back()->withErrors(['form' => 'Account not found.']);
        }

        return redirect()
            ->route('accounts.index')
            ->with('success', 'Account updated successfully.');
    }
}
