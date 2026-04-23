<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

#[Provider('openrouter')]
#[Temperature(0.0)]
#[Timeout(120)]
class InvoiceAnalysisAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return config('ai.invoice_analysis.prompt');
    }

    /**
     * Get the agent's structured output schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vendor_name' => $schema->string()->required(),
            'vendor_address' => $schema->string()->required(),
            'invoice_number' => $schema->string()->required(),
            'invoice_date' => $schema->string()->description('ISO-8601 date')->required(),
            'due_date' => $schema->string()->description('ISO-8601 date')->required(),
            'line_items' => $schema->array()->items(
                $schema->object(fn ($s) => [
                    'description' => $s->string()->required(),
                    'quantity' => $s->number()->required(),
                    'unit_price' => $s->number()->required(),
                    'total' => $s->number()->required(),
                ])
            )->required(),
            'subtotal' => $schema->number()->required(),
            'tax' => $schema->number()->required(),
            'total_amount' => $schema->number()->required(),
            'currency' => $schema->string()->description('3-letter ISO code')->required(),
            'notes' => $schema->string()->required(),
        ];
    }
}
