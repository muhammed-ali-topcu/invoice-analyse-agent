<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnalysisResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_id' => $this->invoice_id,
            'json_data' => $this->json_data,
            'llm_name' => $this->llm_name,
            'prompt_text' => $this->prompt_text,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
