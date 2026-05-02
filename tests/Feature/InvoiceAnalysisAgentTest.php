<?php

use App\Ai\Agents\InvoiceAnalysisAgent;
use App\Models\Option;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('invoice analysis agent uses provider from settings', function () {
    Option::factory()->create(['key' => 'ai_provider', 'value' => 'anthropic']);

    $agent = app(InvoiceAnalysisAgent::class);

    expect($agent->provider())->toBe('anthropic');
});

test('invoice analysis agent uses model from settings', function () {
    Option::factory()->create(['key' => 'ai_model', 'value' => 'claude-3-opus']);

    $agent = app(InvoiceAnalysisAgent::class);

    expect($agent->model())->toBe('claude-3-opus');
});

test('invoice analysis agent uses prompt from settings', function () {
    Option::factory()->create(['key' => 'ai_prompt', 'value' => 'Custom prompt']);

    $agent = app(InvoiceAnalysisAgent::class);

    expect((string) $agent->instructions())->toBe('Custom prompt');
});

test('invoice analysis agent falls back to default prompt if setting is missing', function () {
    $agent = app(InvoiceAnalysisAgent::class);

    expect((string) $agent->instructions())->toBe(config('ai.invoice_analysis.prompt'));
});
