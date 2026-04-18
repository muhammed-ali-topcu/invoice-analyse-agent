<?php

namespace App\Repositories;

use App\Models\Invoice;
use App\Repositories\Contracts\InvoiceRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
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

    /**
     * {@inheritDoc}
     */
    public function getPaginatedList(?string $search = null, ?string $status = null, string $sortField = 'uploaded_at', string $sortDirection = 'desc', int $perPage = 10): LengthAwarePaginator
    {
        $query = Invoice::query();

        if ($search) {
            $query->where('original_file_name', 'like', "%{$search}%");
        }

        if ($status) {
            $query->where('status', $status);
        }

        return $query->orderBy($sortField, $sortDirection)->paginate($perPage);
    }
}
