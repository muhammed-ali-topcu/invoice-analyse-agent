<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Repositories\Contracts\InvoiceRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class InvoiceUploadService
{
    public function __construct(
        private readonly InvoiceRepositoryInterface $invoiceRepository,
    ) {}

    /**
     * Store an uploaded invoice file and persist its record.
     */
    public function store(UploadedFile $file): Invoice
    {
        $uniqueName = Str::uuid().'.'.$file->getClientOriginalExtension();
        $path = $file->storeAs('invoices', $uniqueName, 'public');

        return $this->invoiceRepository->create([
            'original_file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'status' => InvoiceStatus::Pending->value,
            'uploaded_at' => now()->toDateTimeString(),
        ]);
    }
}
