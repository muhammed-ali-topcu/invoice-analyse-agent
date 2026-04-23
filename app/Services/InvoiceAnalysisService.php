<?php

namespace App\Services;

use App\Ai\Agents\InvoiceAnalysisAgent;
use App\Models\Analysis;
use App\Models\Invoice;
use App\Repositories\Contracts\AnalysisRepositoryInterface;
use Laravel\Ai\Files\Image;

class InvoiceAnalysisService
{
    public function __construct(
        private readonly AnalysisRepositoryInterface $analysisRepository,
    ) {}

    /**
     * Analyse an invoice image and persist its record.
     */
    public function analyse(Invoice $invoice): Analysis
    {
        // Resolve image from the public disk
        $image = Image::fromStorage($invoice->file_path);

        $promptText = config('ai.invoice_analysis.prompt');
        $model = config('ai.invoice_analysis.model');

        // Call the agent with the per-prompt model override
        $response = (new InvoiceAnalysisAgent)->prompt(
            $promptText,
            model: $model,
            attachments: [$image],
        );

        // Cast StructuredAgentResponse to array
        $jsonData = $response->toArray();

        // Persist via repository
        return $this->analysisRepository->create([
            'invoice_id' => $invoice->id,
            'json_data' => $jsonData,
            'llm_name' => $model,
            'prompt_text' => $promptText,
        ]);
    }
}
