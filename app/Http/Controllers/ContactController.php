<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactRequest;
use App\Http\Requests\UpdateContactRequest;
use App\Repositories\Contracts\ContactRepositoryInterface;
use App\Services\BalanceTrendChartBuilder;
use App\Services\ReportService;
use App\Models\Transaction;
use App\Support\BalanceTrendPeriod;
use App\Support\MoneyFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function __construct(
        private readonly ContactRepositoryInterface $contactRepository,
        private readonly ReportService $reportService,
        private readonly BalanceTrendChartBuilder $balanceTrendChartBuilder,
    ) {
    }

    public function overview(Request $request): View
    {
        $period = BalanceTrendPeriod::resolve($request->input('period'));
        $people = $this->contactRepository->findByType('person');
        $companies = $this->contactRepository->findByType('company');
        $overview = $this->reportService->lendingOverviewLedger($period);

        return view('lending.overview', [
            'people' => $people,
            'companies' => $companies,
            'totalPeople' => $people->count() + $companies->count(),
            'overview' => $overview,
            'period' => $period,
        ]);
    }

    public function index(): View
    {
        return view('lending.people.index', [
            'people' => $this->contactRepository->findByType('person'),
            'companies' => $this->contactRepository->findByType('company'),
        ]);
    }

    public function create(): View
    {
        return view('lending.people.create');
    }

    public function edit(int $id): View
    {
        $contact = $this->contactRepository->find($id);

        if ($contact === null) {
            abort(404);
        }

        return view('lending.people.edit', [
            'contact' => $contact,
        ]);
    }

    public function ledger(Request $request): View
    {
        $period = BalanceTrendPeriod::resolve($request->input('period'));
        $contactId = (int) $request->input('contact_id');

        if ($contactId > 0) {
            $ledger = $this->reportService->contactBalanceLedger($contactId, $period);

            return view('lending.ledger', [
                'contacts' => $this->contactRepository->allActive(),
                'transactions' => $ledger['transactions'],
                'summary' => $ledger['summary'],
                'chart' => $ledger['chart'],
                'overview' => null,
                'selectedContactId' => $contactId,
                'selectedContact' => $this->contactRepository->find($contactId),
                'baseCurrency' => MoneyFormatter::baseCurrency(),
                'period' => $period,
            ]);
        }

        $overview = $this->reportService->lendingOverviewLedger($period);

        return view('lending.ledger', [
            'contacts' => $this->contactRepository->allActive(),
            'transactions' => $overview['transactions'],
            'summary' => $overview['summary'],
            'chart' => $overview['chart'],
            'overview' => $overview,
            'selectedContactId' => null,
            'selectedContact' => null,
            'baseCurrency' => MoneyFormatter::baseCurrency(),
            'period' => $period,
        ]);
    }

    public function trendChart(Request $request): JsonResponse
    {
        $period = BalanceTrendPeriod::resolve($request->input('period'));
        $contactId = (int) $request->input('contact_id');

        $query = Transaction::query();

        if ($contactId > 0) {
            $query->where('contact_id', $contactId);
        } else {
            $query->whereNotNull('contact_id');
        }

        return response()->json(
            $this->balanceTrendChartBuilder->build($query, $period)
        );
    }

    public function store(StoreContactRequest $request): RedirectResponse
    {
        $this->contactRepository->create(array_merge(
            $request->validated(),
            ['current_balance' => '0.0000']
        ));

        return redirect()
            ->route('lending.people.index')
            ->with('success', 'Contact added successfully.');
    }

    public function update(UpdateContactRequest $request, int $id): RedirectResponse
    {
        $updated = $this->contactRepository->update($id, $request->validated());

        if (! $updated) {
            return back()->withErrors(['form' => 'Contact not found.']);
        }

        return redirect()
            ->route('lending.people.index')
            ->with('success', 'Contact updated successfully.');
    }
}
