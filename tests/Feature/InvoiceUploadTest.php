<?php

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
});

test('unauthenticated user is redirected from upload page', function () {
    $this->get(route('invoices.upload'))
        ->assertRedirect(route('login'));
});

test('unauthenticated user cannot post invoices', function () {
    $this->post(route('invoices.store'))
        ->assertRedirect(route('login'));
});

test('authenticated user can view the upload page', function () {
    $user = User::factory()->create();

    $this->withoutVite();

    $this->actingAs($user)
        ->get(route('invoices.upload'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Invoices/Upload'));
});

test('authenticated user can upload a single invoice image', function () {
    $user = User::factory()->create();
    $file = UploadedFile::fake()->image('invoice.jpg', 100, 100);

    $this->actingAs($user)
        ->post(route('invoices.store'), ['invoices' => [$file]])
        ->assertRedirect();

    expect(Invoice::count())->toBe(1);

    $invoice = Invoice::first();
    expect($invoice->original_file_name)->toBe('invoice.jpg')
        ->and($invoice->status)->toBe(InvoiceStatus::Pending)
        ->and($invoice->mime_type)->not->toBeEmpty()
        ->and($invoice->file_size)->toBeGreaterThan(0);

    Storage::disk('public')->assertExists($invoice->file_path);
});

test('authenticated user can upload multiple invoice images at once', function () {
    $user = User::factory()->create();
    $files = [
        UploadedFile::fake()->image('first.jpg'),
        UploadedFile::fake()->image('second.png'),
        UploadedFile::fake()->image('third.jpeg'),
    ];

    $this->actingAs($user)
        ->post(route('invoices.store'), ['invoices' => $files])
        ->assertRedirect();

    expect(Invoice::count())->toBe(3);

    Invoice::all()->each(function (Invoice $invoice) {
        Storage::disk('public')->assertExists($invoice->file_path);
        expect($invoice->status)->toBe(InvoiceStatus::Pending);
    });
});

test('uploading an invalid file type returns a validation error', function () {
    $user = User::factory()->create();
    $file = UploadedFile::fake()->create('malware.exe', 100, 'application/octet-stream');

    $this->actingAs($user)
        ->post(route('invoices.store'), ['invoices' => [$file]])
        ->assertSessionHasErrors('invoices.0');

    expect(Invoice::count())->toBe(0);
});

test('uploading without any files returns a validation error', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('invoices.store'), [])
        ->assertSessionHasErrors('invoices');
});

test('uploading a file that exceeds 10 MB returns a validation error', function () {
    $user = User::factory()->create();
    $file = UploadedFile::fake()->image('huge.jpg')->size(11_000);

    $this->actingAs($user)
        ->post(route('invoices.store'), ['invoices' => [$file]])
        ->assertSessionHasErrors('invoices.0');

    expect(Invoice::count())->toBe(0);
});
