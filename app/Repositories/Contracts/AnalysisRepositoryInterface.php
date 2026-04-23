<?php

namespace App\Repositories\Contracts;

use App\Models\Analysis;
use App\Models\Invoice;
use Illuminate\Support\Collection;

interface AnalysisRepositoryInterface
{
    /**
     * Persist a new analysis record.
     *
     * @param array{
     *     invoice_id: int,
     *     json_data: array,
     *     llm_name: string,
     *     prompt_text: string,
     * } $data
     */
    public function create(array $data): Analysis;

    /**
     * Find an analysis by its primary key.
     */
    public function findById(int $id): Analysis;

    /**
     * Find analyses for a given invoice.
     *
     * @return Collection<int, Analysis>
     */
    public function findByInvoice(Invoice $invoice): Collection;
}
