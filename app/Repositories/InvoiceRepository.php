<?php

namespace App\Repositories;

use App\Models\Invoice;
use App\Repositories\Contracts\InvoiceRepositoryInterface;
use Illuminate\Support\Collection;

class InvoiceRepository implements InvoiceRepositoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function create(array $data): Invoice
    {
        return Invoice::create($data);
    }

    /**
     * {@inheritDoc}
     */
    public function findById(int $id): Invoice
    {
        return Invoice::findOrFail($id);
    }

    /**
     * {@inheritDoc}
     */
    public function all(): Collection
    {
        return Invoice::all();
    }
}
