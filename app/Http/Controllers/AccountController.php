<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreAccountRequest;
use App\Http\Requests\UpdateAccountRequest;
use App\Models\Account;
use App\Models\Currency;
use App\Repositories\Contracts\AccountRepositoryInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function __construct(
        private readonly AccountRepositoryInterface $accountRepository,
    ) {
    }

    public function index(Request $request): View
    {
        $status = $request->input('status');
        $statusFilter = in_array($status, ['active', 'inactive'], true) ? $status : null;

        return view('accounts.index', [
            'accounts' => $this->accountRepository->all($statusFilter),
            'currencies' => Currency::query()->orderBy('name')->get(),
            'filters' => $request->only(['status']),
            'stats' => [
                'total' => Account::query()->count(),
                'active' => Account::query()->where('status', 'active')->count(),
                'archived' => Account::query()->where('status', 'inactive')->count(),
            ],
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
            'status' => 'active',
        ]);

        return redirect()
            ->route('accounts.index')
            ->with('success', 'Account created successfully.');
    }

    public function update(UpdateAccountRequest $request, int $id): RedirectResponse
    {
        $account = $this->accountRepository->find($id);

        if ($account === null) {
            return back()->withErrors(['form' => 'Account not found.']);
        }

        $data = $request->validated();

        if ($account->transactions()->exists()) {
            unset($data['currency_id']);
        }

        $updated = $this->accountRepository->update($id, $data);

        if (! $updated) {
            return back()->withErrors(['form' => 'Account not found.']);
        }

        return redirect()
            ->route('accounts.index')
            ->with('success', 'Account updated successfully.');
    }

    public function archive(int $id): RedirectResponse
    {
        $account = $this->accountRepository->find($id);

        if ($account === null) {
            return back()->withErrors(['form' => 'Account not found.']);
        }

        $this->accountRepository->update($id, ['status' => 'inactive']);

        return redirect()
            ->route('accounts.index')
            ->with('success', 'Account archived. It will no longer appear in new transactions.');
    }

    public function restore(int $id): RedirectResponse
    {
        $account = $this->accountRepository->find($id);

        if ($account === null) {
            return back()->withErrors(['form' => 'Account not found.']);
        }

        $this->accountRepository->update($id, ['status' => 'active']);

        return redirect()
            ->route('accounts.index')
            ->with('success', 'Account restored and available for new transactions.');
    }
}
