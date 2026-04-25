<?php

namespace App\Repositories\Contracts;

use App\Models\Invoice;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface InvoiceRepositoryInterface
{
    /**
     * Persist a new invoice record.
     *
     * @param array{
     *     original_file_name: string,
     *     file_path: string,
     *     mime_type: string,
     *     file_size: int,
     *     status: string,
     *     uploaded_at: string,
     * } $data
     */
    public function create(array $data): Invoice;

    /**
     * Find an invoice by its primary key.
     */
    public function findById(int $id): Invoice;

    /**
     * Find an invoice by its primary key with the latest analysis loaded.
     */
    public function findByIdWithLatestAnalysis(int $id): Invoice;

    /**
     * Find an invoice by its primary key with all analyses loaded.
     */
    public function findByIdWithAllAnalyses(int $id): Invoice;

    /**
     * Return all invoice records.
     *
     * @return Collection<int, Invoice>
     */
    public function all(): Collection;

    /**
     * Get paginated invoices with optional search, status filtering, and sorting.
     */
    public function getPaginatedList(?string $search = null, ?string $status = null, string $sortField = 'uploaded_at', string $sortDirection = 'desc', int $perPage = 10): LengthAwarePaginator;
}
