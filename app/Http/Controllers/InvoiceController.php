<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Resources\InvoiceResource;
use App\Services\InvoiceUploadService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Inertia\Inertia;
use Inertia\Response;

class InvoiceController extends Controller
{
    public function __construct(
        private readonly InvoiceUploadService $invoiceUploadService,
    ) {}

    /**
     * Show the invoice upload page.
     */
    public function index(): Response
    {
        return Inertia::render('Invoices/Upload');
    }

    /**
     * Store one or more uploaded invoice images.
     */
    public function store(StoreInvoiceRequest $request): AnonymousResourceCollection
    {
        $invoices = collect($request->file('invoices'))
            ->map(fn ($file) => $this->invoiceUploadService->store($file));

        return InvoiceResource::collection($invoices);
    }
}
