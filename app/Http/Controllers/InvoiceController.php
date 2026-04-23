<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Resources\AnalysisResource;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Repositories\Contracts\InvoiceRepositoryInterface;
use App\Services\InvoiceAnalysisService;
use App\Services\InvoiceUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Inertia\Inertia;
use Inertia\Response;

class InvoiceController extends Controller
{
    public function __construct(
        private readonly InvoiceUploadService $invoiceUploadService,
        private readonly InvoiceRepositoryInterface $invoiceRepository,
        private readonly InvoiceAnalysisService $analysisService,
    ) {}

    /**
     * Show the invoice list page.
     */
    public function index(Request $request): Response
    {
        $search = $request->query('search');
        $status = $request->query('status');
        $sort = $request->query('sort', 'uploaded_at');
        $direction = $request->query('direction', 'desc');

        $invoices = $this->invoiceRepository->getPaginatedList($search, $status, $sort, $direction, 10);
        $invoices->withQueryString();

        return Inertia::render('Invoices/Index', [
            'invoices' => InvoiceResource::collection($invoices),
            'filters' => $request->only(['search', 'status', 'sort', 'direction']),
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
    public function store(StoreInvoiceRequest $request): AnonymousResourceCollection
    {
        $invoices = collect($request->file('invoices'))
            ->map(fn ($file) => $this->invoiceUploadService->store($file));

        return InvoiceResource::collection($invoices);
    }

    /**
     * Analyse an invoice image and store the extracted data.
     */
    public function analyse(Invoice $invoice): JsonResponse
    {
        $analysis = $this->analysisService->analyse($invoice);

        return (new AnalysisResource($analysis))
            ->response()
            ->setStatusCode(201);
    }
}
