<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['original_file_name', 'file_path', 'mime_type', 'file_size', 'status', 'uploaded_at'])]
class Invoice extends Model
{
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => InvoiceStatus::class,
            'uploaded_at' => 'datetime',
            'file_size' => 'integer',
        ];
    }

    /**
     * Get the analyses associated with the invoice.
     */
    public function analyses(): HasMany
    {
        return $this->hasMany(Analysis::class);
    }
}
