<?php

namespace Database\Factories;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'original_file_name' => fake()->word().'.jpg',
            'file_path' => 'invoices/'.fake()->uuid().'.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => fake()->numberBetween(1000, 5000000),
            'status' => InvoiceStatus::Pending,
            'uploaded_at' => now(),
        ];
    }
}
