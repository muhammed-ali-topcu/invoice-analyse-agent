<?php

namespace App\Services;

use App\Models\Invoice;
use App\Repositories\Contracts\InvoiceRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class InvoiceGetter
{
    public function __construct(
        private readonly InvoiceRepositoryInterface $invoiceRepository,
    ) {}

    /**
     * Get paginated invoices with optional filters.
     */
    public function getPaginatedInvoices(array $params, int $perPage = 10): LengthAwarePaginator
    {
        return $this->invoiceRepository->getPaginatedList(
            search: $params['search'] ?? null,
            status: $params['status'] ?? null,
            sortField: $params['sort'] ?? 'uploaded_at',
            sortDirection: $params['direction'] ?? 'desc',
            perPage: $perPage
        );
    }

    /**
     * Get a single invoice with its latest analysis.
     */
    public function getInvoiceWithLatestAnalysis(int $id): Invoice
    {
        return $this->invoiceRepository->findByIdWithLatestAnalysis($id);
    }

    /**
     * Get a single invoice with all its analyses.
     */
    public function getInvoiceWithAllAnalyses(int $id): Invoice
    {
        return $this->invoiceRepository->findByIdWithAllAnalyses($id);
    }

    /**
     * Get a single invoice by ID.
     */
    public function getInvoiceById(int $id): Invoice
    {
        return $this->invoiceRepository->findById($id);
    }
}
