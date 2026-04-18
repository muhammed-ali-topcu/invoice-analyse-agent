<?php

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('unauthenticated users cannot view invoices list', function () {
    $this->get(route('invoices.index'))
        ->assertRedirect(route('login'));
});

test('authenticated users can view invoices list', function () {
    $user = User::factory()->create();

    Invoice::factory()->count(3)->create([
        'status' => InvoiceStatus::Pending,
    ]);

    $this->actingAs($user)
        ->get(route('invoices.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Invoices/Index')
            ->has('invoices.data', 3)
            ->has('filters')
        );
});

test('invoices can be searched by name', function () {
    $user = User::factory()->create();

    Invoice::factory()->create(['original_file_name' => 'receipt-123.jpg']);
    Invoice::factory()->create(['original_file_name' => 'invoice-456.png']);

    $this->actingAs($user)
        ->get(route('invoices.index', ['search' => '123']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('invoices.data', 1)
            ->where('invoices.data.0.original_file_name', 'receipt-123.jpg')
        );
});

test('invoices can be filtered by status', function () {
    $user = User::factory()->create();

    Invoice::factory()->create(['status' => InvoiceStatus::Pending]);
    Invoice::factory()->create(['status' => InvoiceStatus::Completed]);

    $this->actingAs($user)
        ->get(route('invoices.index', ['status' => InvoiceStatus::Completed->value]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('invoices.data', 1)
            ->where('invoices.data.0.status', InvoiceStatus::Completed->value)
        );
});

test('invoices can be sorted', function () {
    $user = User::factory()->create();

    $invoice1 = Invoice::factory()->create(['file_size' => 100]);
    $invoice2 = Invoice::factory()->create(['file_size' => 200]);

    $this->actingAs($user)
        ->get(route('invoices.index', ['sort' => 'file_size', 'direction' => 'desc']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('invoices.data', 2)
            ->where('invoices.data.0.id', $invoice2->id)
            ->where('invoices.data.1.id', $invoice1->id)
        );
});
