<?php

namespace App\Ai\Agents;

use App\Repositories\Contracts\OptionRepositoryInterface;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

#[Temperature(0.0)]
#[Timeout(120)]
class InvoiceAnalysisAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    /**
     * Create a new agent instance.
     */
    public function __construct(protected OptionRepositoryInterface $options) {}

    /**
     * Get the provider that the agent should use.
     */
    public function provider(): ?string
    {
        return $this->options->getValue('ai_provider');
    }

    /**
     * Get the model that the agent should use.
     */
    public function model(): ?string
    {
        return $this->options->getValue('ai_model');
    }

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return $this->options->getValue('ai_prompt', config('ai.invoice_analysis.prompt'));
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
                    'discount' => $s->number()->nullable(),
                    'presents' => $s->number()->nullable(),
                    'unit_price' => $s->number()->required(),
                    'total' => $s->number()->required(),
                ])
            )->required(),
            'subtotal' => $schema->number()->required(),
            'discount_total' => $schema->number()->nullable(),
            'present_total' => $schema->number()->nullable(),
            'total_amount' => $schema->number()->required(),
            'paid_amount' => $schema->number()->nullable(),
            'dept_amount' => $schema->number()->nullable(),
            'currency' => $schema->string()->description('3-letter ISO code')->required(),
            'notes' => $schema->string()->required(),
        ];
    }
}
