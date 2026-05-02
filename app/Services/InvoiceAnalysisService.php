<?php

namespace App\Services;

use App\Ai\Agents\InvoiceAnalysisAgent;
use App\Enums\InvoiceStatus;
use App\Models\Analysis;
use App\Models\Invoice;
use App\Repositories\Contracts\AnalysisRepositoryInterface;
use Laravel\Ai\Files\Image;

class InvoiceAnalysisService
{
    public function __construct(
        private readonly AnalysisRepositoryInterface $analysisRepository,
        private readonly InvoiceAnalysisAgent $agent,
    ) {}

    /**
     * Analyse an invoice image and persist its record.
     */
    public function analyse(Invoice $invoice): Analysis
    {
        $invoice->update(['status' => InvoiceStatus::Processing]);

        try {
            $image = Image::fromStorage($invoice->file_path, 'public');

            $promptText = (string) $this->agent->instructions();

            // Call the agent without per-prompt overrides to use its dynamic settings
            $response = $this->agent->prompt(
                $promptText,
                attachments: [$image],
            );

            // Cast StructuredAgentResponse to array
            $jsonData = $response->toArray();

            $analysis = $this->analysisRepository->create([
                'invoice_id' => $invoice->id,
                'json_data' => $jsonData,
                'llm_name' => $this->agent->model() ?? config('ai.invoice_analysis.model'),
                'prompt_text' => $promptText,
            ]);

            $invoice->update(['status' => InvoiceStatus::Completed]);

            return $analysis;
        } catch (\Exception $e) {
            $invoice->update(['status' => InvoiceStatus::Failed]);
            throw $e;
        }
    }
}
