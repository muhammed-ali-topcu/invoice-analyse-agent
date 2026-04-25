<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Services\InvoiceAnalysisService;
use App\Services\InvoiceGetter;
use App\Services\InvoiceUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InvoiceController extends Controller
{
    public function __construct(
        private readonly InvoiceUploadService $invoiceUploadService,
        private readonly InvoiceGetter $invoiceGetter,
        private readonly InvoiceAnalysisService $analysisService,
    ) {}

    /**
     * Show the invoice list page.
     */
    public function index(Request $request): Response
    {
        $invoices = $this->invoiceGetter->getPaginatedInvoices($request->all());
        $invoices->withQueryString();

        return Inertia::render('Invoices/Index', [
            'invoices' => InvoiceResource::collection($invoices),
            'filters' => $request->only(['search', 'status', 'sort', 'direction']),
        ]);
    }

    /**
     * Show the invoice detail page.
     */
    public function show(int $id): Response
    {
        $invoice = $this->invoiceGetter->getInvoiceWithAllAnalyses($id);

        return Inertia::render('Invoices/Show', [
            'invoice' => InvoiceResource::make($invoice),
        ]);
    }

    /**
     * Show the invoice upload page.
     */
    public function create(): Response
    {
        return Inertia::render('Invoices/Upload');
    }

    /**
     * Store one or more uploaded invoice images.
     */
    public function store(StoreInvoiceRequest $request): RedirectResponse
    {
        $invoices = collect($request->file('invoices'))
            ->map(fn ($file) => $this->invoiceUploadService->store($file));

        return redirect()->back()->with('invoices', InvoiceResource::collection($invoices)->resolve());
    }

    /**
     * Analyse an invoice image and store the extracted data.
     */
    public function analyse(int $id): RedirectResponse
    {
        $invoice = $this->invoiceGetter->getInvoiceById($id);

        $this->analysisService->analyse($invoice);

        return redirect()->route('invoices.show', $invoice);
    }
}
