<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
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
            'original_file_name' => $this->original_file_name,
            'file_path' => $this->file_path,
            'file_url' => asset('storage/'.$this->file_path),
            'mime_type' => $this->mime_type,
            'file_size' => $this->file_size,
            'status' => $this->status,
            'uploaded_at' => $this->uploaded_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
