<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Contact;
use App\Models\Currency;
use App\Models\PaymentMethod;
use App\Models\TransactionCategory;
use App\Models\User;
use App\Support\ExpenseBuddyTestHarness;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminRouteSmokeTest extends TestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = ExpenseBuddyTestHarness::install(withDemo: true);
        ExpenseBuddyTestHarness::createSampleTransaction();
    }

    #[Test]
    public function administrator_can_access_core_get_routes(): void
    {
        $contact = Contact::query()->firstOrFail();
        $transaction = \App\Models\Transaction::query()->firstOrFail();
        $invoice = \App\Models\TransactionInvoice::query()->firstOrFail();

        $routes = [
            '/',
            '/transactions',
            '/transactions/create',
            '/transfers/create',
            '/accounts',
            '/categories',
            '/lending',
            '/lending/ledger',
            '/lending/people',
            '/lending/people/create',
            '/lending/people/'.$contact->id.'/edit',
            '/reports/income-vs-expense',
            '/reports/categorized',
            '/reports/detailed',
            '/admin/settings',
            '/admin/currencies',
            '/admin/users',
            '/admin/roles',
            '/transactions/'.$transaction->id.'/invoice',
            '/i/'.$invoice->public_token,
        ];

        foreach ($routes as $uri) {
            $this->actingAs($this->admin)->get($uri)->assertOk();
        }

        $this->actingAs($this->admin)->get('/admin/backup')->assertRedirect();
    }

    #[Test]
    public function redirect_routes_resolve_for_administrator(): void
    {
        $this->actingAs($this->admin)
            ->get('/contacts')
            ->assertRedirect('/lending/people');

        $this->actingAs($this->admin)
            ->get('/reports/contact-ledger')
            ->assertRedirect('/lending/ledger');

        $this->actingAs($this->admin)
            ->get('/admin')
            ->assertRedirect();
    }

    #[Test]
    public function pwa_manifest_and_favicon_endpoints_are_available(): void
    {
        $this->get('/manifest.webmanifest')
            ->assertOk()
            ->assertJsonStructure(['name', 'icons', 'start_url']);

        $this->get('/favicon.ico')
            ->assertOk()
            ->assertHeader('Content-Type', 'image/svg+xml');
    }
}
