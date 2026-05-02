<?php

namespace App\Services;

use App\Models\Option;
use App\Repositories\Contracts\OptionRepositoryInterface;
use Illuminate\Support\Collection;

class OptionService
{
    public function __construct(public OptionRepositoryInterface $optionRepository) {}

    /**
     * Get all options.
     */
    public function getAllOptions(): Collection
    {
        return $this->optionRepository->all();
    }

    /**
     * Find a single option by its primary key.
     */
    public function findOption(int $id): ?Option
    {
        return $this->optionRepository->find($id);
    }

    /**
     * Update an option's value by its primary key.
     */
    public function updateOption(int $id, ?string $value): Option
    {
        $option = $this->optionRepository->find($id);

        abort_if($option === null, 404);

        return $this->optionRepository->updateOrCreate($option->key, $value);
    }
}
