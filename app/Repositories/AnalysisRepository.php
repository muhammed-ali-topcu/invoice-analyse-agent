<?php

namespace App\Repositories;

use App\Models\Analysis;
use App\Models\Invoice;
use App\Repositories\Contracts\AnalysisRepositoryInterface;
use Illuminate\Support\Collection;

class AnalysisRepository implements AnalysisRepositoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function create(array $data): Analysis
    {
        return Analysis::create($data);
    }

    /**
     * {@inheritDoc}
     */
    public function findById(int $id): Analysis
    {
        return Analysis::findOrFail($id);
    }

    /**
     * {@inheritDoc}
     */
    public function findByInvoice(Invoice $invoice): Collection
    {
        return $invoice->analyses()->get();
    }
}
