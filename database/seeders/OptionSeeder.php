<?php

namespace Database\Seeders;

use App\Repositories\Contracts\OptionRepositoryInterface;
use Illuminate\Database\Seeder;

class OptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(OptionRepositoryInterface $optionRepository): void
    {
        $options = [
            'ai_provider' => 'openrouter',
            'ai_model' => 'openrouter/gpt-4o',
            'ai_api_key' => null,
            'ai_prompt' => 'You are an expert invoice data extraction specialist. Extract all invoice fields from the attached image and return them as structured JSON. Be precise and thorough.',
        ];

        foreach ($options as $key => $value) {
            $optionRepository->updateOrCreate($key, $value);
        }
    }
}
