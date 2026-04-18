<?php

namespace App\Repositories\Contracts;

use App\Models\Invoice;
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
     * Return all invoice records.
     *
     * @return Collection<int, Invoice>
     */
    public function all(): Collection;
}
