<?php

namespace App\Models;

use Database\Factories\AnalysisFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['invoice_id', 'json_data', 'llm_name', 'prompt_text'])]
class Analysis extends Model
{
    /** @use HasFactory<AnalysisFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'json_data' => 'array',
        ];
    }

    /**
     * Get the invoice that owns the analysis.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
